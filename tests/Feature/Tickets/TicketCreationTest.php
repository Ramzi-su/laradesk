<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_a_ticket_with_attachments(): void
    {
        Storage::fake('local');
        $client = User::factory()->client()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($client)->post(route('tickets.store'), [
            'title' => 'Cannot log in',
            'description' => 'The login form says my password is wrong.',
            'category_id' => $category->id,
            'priority' => 'high',
            'attachments' => [
                UploadedFile::fake()->create('screenshot.png', 200, 'image/png'),
                UploadedFile::fake()->create('invoice.pdf', 300, 'application/pdf'),
            ],
        ]);

        $ticket = Ticket::sole();
        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertTrue($ticket->client->is($client));
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->agent_id);
        $this->assertCount(2, $ticket->attachments);
        foreach ($ticket->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->path);
            $this->assertStringStartsWith("tickets/{$ticket->id}/", $attachment->path);
        }
    }

    public function test_client_cannot_force_status_or_agent(): void
    {
        $client = User::factory()->client()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($client)->post(route('tickets.store'), [
            'title' => 'Title',
            'description' => 'Description',
            'category_id' => Category::factory()->create()->id,
            'priority' => 'low',
            'status' => 'closed',
            'agent_id' => $agent->id,
        ]);

        $ticket = Ticket::sole();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->agent_id);
    }

    public function test_validation_rejects_invalid_data(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->post(route('tickets.store'), [
                'title' => '',
                'description' => '',
                'category_id' => 999,
                'priority' => 'critical',
            ])
            ->assertSessionHasErrors(['title', 'description', 'category_id', 'priority']);

        $this->assertSame(0, Ticket::count());
    }

    public function test_forbidden_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->client()->create())
            ->post(route('tickets.store'), [
                'title' => 'Title',
                'description' => 'Description',
                'category_id' => Category::factory()->create()->id,
                'priority' => 'low',
                'attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Ticket::count());
    }

    public function test_file_larger_than_5_mb_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->client()->create())
            ->post(route('tickets.store'), [
                'title' => 'Title',
                'description' => 'Description',
                'category_id' => Category::factory()->create()->id,
                'priority' => 'low',
                'attachments' => [UploadedFile::fake()->create('big.pdf', 5 * 1024 + 1, 'application/pdf')],
            ])
            ->assertSessionHasErrors('attachments.0');
    }

    public function test_agent_cannot_create_a_ticket(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->get(route('tickets.create'))->assertForbidden();
        $this->actingAs($agent)->post(route('tickets.store'), [])->assertForbidden();
    }
}
