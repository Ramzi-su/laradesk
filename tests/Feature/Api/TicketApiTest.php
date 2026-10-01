<?php

namespace Tests\Feature\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_is_scoped_paginated_and_shaped_by_the_resource(): void
    {
        $client = User::factory()->client()->create();
        Ticket::factory()->count(16)->for($client, 'client')->create();
        Ticket::factory()->count(3)->create();
        Sanctum::actingAs($client);

        $this->getJson(route('api.v1.tickets.index'))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonStructure([
                'data' => [['id', 'reference', 'title', 'status' => ['value', 'label'], 'priority', 'category', 'client', 'agent']],
                'links' => ['first', 'next'],
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonMissingPath('data.0.client_id');
    }

    public function test_list_filters(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();
        $match = Ticket::factory()->for($category)->resolved()->priority(TicketPriority::Urgent)->create(['title' => 'Parcel lost']);
        Ticket::factory()->for($category)->priority(TicketPriority::Urgent)->create(['title' => 'Parcel late']);
        Ticket::factory()->resolved()->priority(TicketPriority::Urgent)->create(['title' => 'Parcel broken']);
        Ticket::factory()->for($category)->resolved()->priority(TicketPriority::Low)->create(['title' => 'Parcel open']);

        $this->getJson(route('api.v1.tickets.index', [
            'status' => 'resolved',
            'priority' => 'urgent',
            'category' => $category->id,
            'search' => 'parcel',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_invalid_filter_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.tickets.index', ['priority' => 'whatever']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('priority');
    }

    public function test_show_returns_comments_without_internal_notes_for_the_client(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->has(Attachment::factory())->create();
        Comment::factory()->for($ticket)->for($agent)->create(['body' => 'Public answer']);
        Comment::factory()->for($ticket)->for($agent)->internal()->create(['body' => 'Secret staff note']);

        Sanctum::actingAs($ticket->client);
        $this->getJson(route('api.v1.tickets.show', $ticket))
            ->assertOk()
            ->assertJsonPath('data.reference', $ticket->reference)
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.body', 'Public answer')
            ->assertJsonPath('data.comments.0.author.id', $agent->id)
            ->assertJsonMissingPath('data.attachments.0.path')
            ->assertDontSee('Secret staff note');

        Sanctum::actingAs($agent);
        $this->getJson(route('api.v1.tickets.show', $ticket))->assertJsonCount(2, 'data.comments');
    }

    public function test_show_another_client_ticket_is_403(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->getJson(route('api.v1.tickets.show', Ticket::factory()->create()))
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_ticket_is_404_without_leaking_the_model_class(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/tickets/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => __('Resource not found.')]);
    }

    public function test_client_can_create_a_ticket(): void
    {
        Storage::fake('local');
        $client = User::factory()->client()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson(route('api.v1.tickets.store'), [
            'title' => 'API ticket',
            'description' => 'Created through the API',
            'category_id' => Category::factory()->create()->id,
            'priority' => 'high',
            'attachments' => [UploadedFile::fake()->create('log.txt', 5, 'text/plain')],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'API ticket')
            ->assertJsonPath('data.status.value', 'open')
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.agent', null)
            ->assertJsonCount(1, 'data.attachments');

        $this->assertStringStartsWith('LD-', $response->json('data.reference'));
    }

    public function test_create_validation_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->client()->create());

        $this->postJson(route('api.v1.tickets.store'), ['title' => '', 'priority' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'category_id', 'priority']);
    }

    public function test_agent_cannot_create_a_ticket(): void
    {
        Sanctum::actingAs(User::factory()->agent()->create());

        $this->postJson(route('api.v1.tickets.store'), [])->assertForbidden();
    }

    public function test_assigned_agent_can_change_the_status(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();
        Sanctum::actingAs($agent);

        $this->patchJson(route('api.v1.tickets.status', $ticket), ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'in_progress');

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    public function test_other_agent_cannot_change_the_status(): void
    {
        $ticket = Ticket::factory()->assignedTo()->create();
        Sanctum::actingAs(User::factory()->agent()->create());

        $this->patchJson(route('api.v1.tickets.status', $ticket), ['status' => 'resolved'])->assertForbidden();
    }

    public function test_client_can_comment_but_not_internally(): void
    {
        $ticket = Ticket::factory()->create();
        Sanctum::actingAs($ticket->client);

        $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Hello'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Hello')
            ->assertJsonPath('data.is_internal', false)
            ->assertJsonPath('data.author.id', $ticket->client_id);

        $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Sneaky', 'is_internal' => true])
            ->assertForbidden();
    }

    public function test_attachment_download_is_authorized(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('tickets/1/doc.pdf', 'content');
        $attachment = Attachment::factory()->create(['path' => 'tickets/1/doc.pdf', 'original_name' => 'doc.pdf']);

        Sanctum::actingAs($attachment->ticket->client);
        $this->get(route('api.v1.attachments.show', $attachment))->assertOk()->assertDownload('doc.pdf');

        Sanctum::actingAs(User::factory()->client()->create());
        $this->getJson(route('api.v1.attachments.show', $attachment))->assertForbidden();
    }

    public function test_api_is_rate_limited(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach (range(1, 60) as $request) {
            $this->getJson(route('api.v1.categories.index'))->assertOk();
        }

        $this->getJson(route('api.v1.categories.index'))
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }
}
