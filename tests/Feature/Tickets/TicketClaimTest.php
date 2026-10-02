<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two agents clicking "Take this ticket" at the same time: the authorization check
 * (ticket still unassigned) and the write happen at different moments.
 */
class TicketClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_agent_can_claim_an_unassigned_ticket(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($ticket->claimFor($agent));

        $this->assertSame($agent->id, $ticket->agent_id);
        $this->assertSame($agent->id, $ticket->fresh()->agent_id);
    }

    public function test_claim_fails_when_another_agent_took_the_ticket_in_the_meantime(): void
    {
        $agentA = User::factory()->agent()->create();
        $agentB = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        // Agent A loaded the ticket while it was still unassigned (stale copy)...
        $seenByA = Ticket::find($ticket->id);
        // ...then agent B's request committed first.
        Ticket::find($ticket->id)->agent()->associate($agentB)->save();

        $this->assertFalse($seenByA->claimFor($agentA));

        $this->assertSame($agentB->id, $ticket->fresh()->agent_id, 'The first claim must win, not the last one.');
    }
}
