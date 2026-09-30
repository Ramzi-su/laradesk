<?php

namespace Tests\Feature\Tickets;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketShowAndUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_see_their_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_client_cannot_see_or_update_another_client_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $intruder = User::factory()->client()->create();

        $this->actingAs($intruder)->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs($intruder)->get(route('tickets.edit', $ticket))->assertForbidden();
        $this->actingAs($intruder)
            ->put(route('tickets.update', $ticket), ['title' => 'Hacked', 'description' => 'Hacked'])
            ->assertForbidden();

        $this->assertNotSame('Hacked', $ticket->fresh()->title);
    }

    public function test_agent_cannot_see_a_ticket_assigned_to_another_agent(): void
    {
        $ticket = Ticket::factory()->assignedTo()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_internal_comments_are_hidden_from_the_client(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();
        Comment::factory()->for($ticket)->for($agent)->create(['body' => 'Public answer']);
        Comment::factory()->for($ticket)->for($agent)->internal()->create(['body' => 'Secret staff note']);

        $this->actingAs($ticket->client)->get(route('tickets.show', $ticket))
            ->assertSee('Public answer')
            ->assertDontSee('Secret staff note');

        $this->actingAs($agent)->get(route('tickets.show', $ticket))
            ->assertSee('Secret staff note');
    }

    public function test_client_can_edit_their_open_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)
            ->put(route('tickets.update', $ticket), ['title' => 'New title', 'description' => 'New description'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('New title', $ticket->fresh()->title);
    }

    public function test_client_cannot_edit_a_ticket_in_progress(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();

        $this->actingAs($ticket->client)
            ->put(route('tickets.update', $ticket), ['title' => 'New title', 'description' => 'New description'])
            ->assertForbidden();
    }

    public function test_only_admin_can_soft_delete_a_ticket(): void
    {
        $ticket = Ticket::factory()->assignedTo()->create();

        $this->actingAs($ticket->client)->delete(route('tickets.destroy', $ticket))->assertForbidden();
        $this->actingAs($ticket->agent)->delete(route('tickets.destroy', $ticket))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertSoftDeleted($ticket);
        $this->actingAs($ticket->client)->get(route('tickets.show', $ticket))->assertNotFound();
    }
}
