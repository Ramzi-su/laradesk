<?php

namespace Tests\Feature\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_only_sees_their_own_tickets(): void
    {
        $client = User::factory()->client()->create();
        $own = Ticket::factory()->for($client, 'client')->create();
        Ticket::factory()->create();

        $this->assertEquals([$own->id], Ticket::forUser($client)->pluck('id')->all());
    }

    public function test_agent_sees_assigned_and_unassigned_tickets_only(): void
    {
        $agent = User::factory()->agent()->create();
        $assigned = Ticket::factory()->assignedTo($agent)->create();
        $unassigned = Ticket::factory()->create();
        Ticket::factory()->assignedTo()->create();

        $this->assertEqualsCanonicalizing(
            [$assigned->id, $unassigned->id],
            Ticket::forUser($agent)->pluck('id')->all(),
        );
    }

    public function test_admin_sees_every_ticket(): void
    {
        Ticket::factory()->count(2)->create();
        Ticket::factory()->assignedTo()->create();

        $this->assertSame(3, Ticket::forUser(User::factory()->admin()->create())->count());
    }

    public function test_search_matches_reference_title_and_description(): void
    {
        $byTitle = Ticket::factory()->create(['title' => 'Printer on fire']);
        $byDescription = Ticket::factory()->create(['description' => 'The printer smokes.']);
        Ticket::factory()->create(['title' => 'Other', 'description' => 'Nothing here.']);

        $this->assertEqualsCanonicalizing(
            [$byTitle->id, $byDescription->id],
            Ticket::search('printer')->pluck('id')->all(),
        );
        $this->assertEquals([$byTitle->id], Ticket::search($byTitle->reference)->pluck('id')->all());
    }

    public function test_search_cannot_leak_tickets_of_other_users(): void
    {
        $client = User::factory()->client()->create();
        Ticket::factory()->for($client, 'client')->create(['title' => 'Mine']);
        Ticket::factory()->create(['title' => 'Secret', 'description' => 'Secret too']);

        $this->assertSame(0, Ticket::forUser($client)->search('Secret')->count());
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $literal = Ticket::factory()->create(['title' => 'Discount of 50% not applied']);
        // Would match "%50%%" if the "%" typed by the user was treated as a wildcard.
        Ticket::factory()->create(['title' => 'Order of 500 units', 'description' => 'Missing.']);
        Ticket::factory()->create(['title' => 'Missing file', 'description' => 'Not found.']);

        $this->assertEquals([$literal->id], Ticket::search('50%')->pluck('id')->all());
        $this->assertSame(0, Ticket::search('_issing')->count());
    }

    public function test_blank_search_does_not_filter(): void
    {
        Ticket::factory()->count(2)->create();

        $this->assertSame(2, Ticket::search('  ')->count());
    }

    public function test_status_priority_open_and_assigned_to_scopes(): void
    {
        $agent = User::factory()->agent()->create();
        Ticket::factory()->priority(TicketPriority::Urgent)->create();
        Ticket::factory()->priority(TicketPriority::Low)->inProgress()->assignedTo($agent)->create();
        Ticket::factory()->priority(TicketPriority::Low)->resolved()->create();
        Ticket::factory()->priority(TicketPriority::Low)->closed()->create();

        $this->assertSame(1, Ticket::status(TicketStatus::Resolved)->count());
        $this->assertSame(1, Ticket::status('closed')->count());
        $this->assertSame(1, Ticket::priority(TicketPriority::Urgent)->count());
        $this->assertSame(2, Ticket::open()->count());
        $this->assertSame(1, Ticket::assignedTo($agent)->count());
    }

    public function test_internal_comments_are_hidden_from_clients_only(): void
    {
        $ticket = Ticket::factory()->create();
        Comment::factory()->for($ticket)->create();
        Comment::factory()->for($ticket)->internal()->create();

        $this->assertSame(1, $ticket->comments()->visibleTo($ticket->client)->count());
        $this->assertSame(2, $ticket->comments()->visibleTo(User::factory()->agent()->create())->count());
        $this->assertSame(2, $ticket->comments()->visibleTo(User::factory()->admin()->create())->count());
    }
}
