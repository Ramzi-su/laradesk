<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_change_the_status_of_an_assigned_ticket(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)
            ->patch(route('tickets.status', $ticket), ['status' => 'resolved'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_agent_cannot_change_the_status_of_a_ticket_assigned_to_another_agent(): void
    {
        $ticket = Ticket::factory()->assignedTo()->create();

        $this->actingAs(User::factory()->agent()->create())
            ->patch(route('tickets.status', $ticket), ['status' => 'resolved'])
            ->assertForbidden();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_status_must_change(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)
            ->patch(route('tickets.status', $ticket), ['status' => 'open'])
            ->assertSessionHasErrors('status');
    }

    public function test_client_can_close_their_resolved_ticket_only(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs($ticket->client)
            ->patch(route('tickets.status', $ticket), ['status' => 'open'])
            ->assertSessionHasErrors('status');

        $this->actingAs($ticket->client)
            ->patch(route('tickets.status', $ticket), ['status' => 'closed'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_client_cannot_close_a_ticket_that_is_not_resolved(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)
            ->patch(route('tickets.status', $ticket), ['status' => 'closed'])
            ->assertForbidden();
    }

    public function test_agent_can_change_the_priority_of_an_assigned_ticket(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->priority(TicketPriority::Low)->assignedTo($agent)->create();

        $this->actingAs($agent)->patch(route('tickets.priority', $ticket), ['priority' => 'urgent']);
        $this->assertSame(TicketPriority::Urgent, $ticket->fresh()->priority);

        $this->actingAs($ticket->client)
            ->patch(route('tickets.priority', $ticket), ['priority' => 'low'])
            ->assertForbidden();
    }

    public function test_agent_can_take_an_unassigned_ticket_for_themselves_only(): void
    {
        $agent = User::factory()->agent()->create();
        $colleague = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)
            ->patch(route('tickets.assignment', $ticket), ['agent_id' => $colleague->id])
            ->assertSessionHasErrors('agent_id');

        $this->actingAs($agent)
            ->patch(route('tickets.assignment', $ticket), ['agent_id' => $agent->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($agent->id, $ticket->fresh()->agent_id);
    }

    public function test_agent_cannot_take_a_ticket_already_assigned(): void
    {
        $ticket = Ticket::factory()->assignedTo()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->patch(route('tickets.assignment', $ticket), ['agent_id' => $agent->id])
            ->assertForbidden();
    }

    public function test_admin_can_assign_an_agent_but_not_a_client(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->patch(route('tickets.assignment', $ticket), ['agent_id' => $ticket->client_id])
            ->assertSessionHasErrors('agent_id');

        $this->actingAs($admin)->patch(route('tickets.assignment', $ticket), ['agent_id' => $agent->id]);
        $this->assertSame($agent->id, $ticket->fresh()->agent_id);

        $this->actingAs($admin)->patch(route('tickets.assignment', $ticket), ['agent_id' => null]);
        $this->assertNull($ticket->fresh()->agent_id);
    }
}
