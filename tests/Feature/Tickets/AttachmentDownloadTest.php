<?php

namespace Tests\Feature\Tickets;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Attachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('tickets/1/file.pdf', 'PDF content');

        $this->attachment = Attachment::factory()
            ->for(Ticket::factory()->assignedTo())
            ->create(['path' => 'tickets/1/file.pdf', 'original_name' => 'invoice.pdf']);
    }

    public function test_ticket_owner_can_download_an_attachment(): void
    {
        $this->actingAs($this->attachment->ticket->client)
            ->get(route('attachments.show', $this->attachment))
            ->assertOk()
            ->assertDownload('invoice.pdf');
    }

    public function test_assigned_agent_can_download_an_attachment(): void
    {
        $this->actingAs($this->attachment->ticket->agent)
            ->get(route('attachments.show', $this->attachment))
            ->assertOk();
    }

    public function test_other_users_cannot_download_an_attachment(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->get(route('attachments.show', $this->attachment))
            ->assertForbidden();

        $this->actingAs(User::factory()->agent()->create())
            ->get(route('attachments.show', $this->attachment))
            ->assertForbidden();
    }

    public function test_guests_cannot_download_an_attachment(): void
    {
        $this->get(route('attachments.show', $this->attachment))->assertRedirect(route('login'));
    }
}
