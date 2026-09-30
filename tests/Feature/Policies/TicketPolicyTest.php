<?php

namespace Tests\Feature\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The role matrix from LARADESK_PLAN.md (section 5), rule by rule.
 */
class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $otherClient;

    private User $agent;

    private User $otherAgent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        $this->otherClient = User::factory()->client()->create();
        $this->agent = User::factory()->agent()->create();
        $this->otherAgent = User::factory()->agent()->create();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_view(): void
    {
        $unassigned = Ticket::factory()->for($this->client, 'client')->create();
        $assignedToOther = Ticket::factory()->for($this->client, 'client')->assignedTo($this->otherAgent)->create();

        $this->assertTrue($this->client->can('view', $unassigned));
        $this->assertFalse($this->otherClient->can('view', $unassigned));
        $this->assertTrue($this->agent->can('view', $unassigned));
        $this->assertFalse($this->agent->can('view', $assignedToOther));
        $this->assertTrue($this->otherAgent->can('view', $assignedToOther));
        $this->assertTrue($this->admin->can('view', $assignedToOther));
    }

    public function test_create(): void
    {
        $this->assertTrue($this->client->can('create', Ticket::class));
        $this->assertFalse($this->agent->can('create', Ticket::class));
        $this->assertTrue($this->admin->can('create', Ticket::class));
    }

    public function test_update_title_and_description(): void
    {
        $open = Ticket::factory()->for($this->client, 'client')->create();
        $inProgress = Ticket::factory()->for($this->client, 'client')->inProgress()->assignedTo($this->agent)->create();

        $this->assertTrue($this->client->can('update', $open));
        $this->assertFalse($this->client->can('update', $inProgress));
        $this->assertFalse($this->otherClient->can('update', $open));
        $this->assertFalse($this->agent->can('update', $inProgress));
        $this->assertTrue($this->admin->can('update', $inProgress));
    }

    public function test_change_status(): void
    {
        $resolved = Ticket::factory()->for($this->client, 'client')->resolved()->assignedTo($this->agent)->create();
        $inProgress = Ticket::factory()->for($this->client, 'client')->inProgress()->assignedTo($this->agent)->create();

        $this->assertTrue($this->client->can('changeStatus', $resolved));
        $this->assertFalse($this->client->can('changeStatus', $inProgress));
        $this->assertFalse($this->otherClient->can('changeStatus', $resolved));
        $this->assertTrue($this->agent->can('changeStatus', $inProgress));
        $this->assertFalse($this->otherAgent->can('changeStatus', $inProgress));
        $this->assertTrue($this->admin->can('changeStatus', $inProgress));
    }

    public function test_change_priority(): void
    {
        $ticket = Ticket::factory()->for($this->client, 'client')->assignedTo($this->agent)->create();

        $this->assertFalse($this->client->can('changePriority', $ticket));
        $this->assertTrue($this->agent->can('changePriority', $ticket));
        $this->assertFalse($this->otherAgent->can('changePriority', $ticket));
        $this->assertTrue($this->admin->can('changePriority', $ticket));
    }

    public function test_assign(): void
    {
        $unassigned = Ticket::factory()->for($this->client, 'client')->create();
        $assigned = Ticket::factory()->for($this->client, 'client')->assignedTo($this->agent)->create();

        $this->assertFalse($this->client->can('assign', $unassigned));
        $this->assertTrue($this->agent->can('assign', $unassigned));
        $this->assertFalse($this->otherAgent->can('assign', $assigned));
        $this->assertTrue($this->admin->can('assign', $assigned));
    }

    public function test_comment_and_internal_comment(): void
    {
        $ticket = Ticket::factory()->for($this->client, 'client')->assignedTo($this->agent)->create();

        $this->assertTrue($this->client->can('comment', $ticket));
        $this->assertFalse($this->client->can('commentInternally', $ticket));
        $this->assertFalse($this->otherClient->can('comment', $ticket));
        $this->assertTrue($this->agent->can('commentInternally', $ticket));
        $this->assertFalse($this->otherAgent->can('comment', $ticket));
        $this->assertTrue($this->admin->can('commentInternally', $ticket));
    }

    public function test_only_admin_can_delete(): void
    {
        $ticket = Ticket::factory()->for($this->client, 'client')->assignedTo($this->agent)->create();

        $this->assertFalse($this->client->can('delete', $ticket));
        $this->assertFalse($this->agent->can('delete', $ticket));
        $this->assertTrue($this->admin->can('delete', $ticket));
    }
}
