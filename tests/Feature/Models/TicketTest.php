<?php

namespace Tests\Feature\Models;

use App\Enums\TicketStatus;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_is_generated_from_the_id(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertSame(sprintf('LD-%06d', $ticket->id), $ticket->reference);
        $this->assertSame($ticket->reference, $ticket->fresh()->reference);
    }

    public function test_reference_generation_does_not_change_the_activity_date(): void
    {
        $createdAt = now()->subDays(10)->startOfSecond();

        $ticket = Ticket::factory()->create(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        $this->assertTrue($ticket->fresh()->updated_at->equalTo($createdAt));
    }

    public function test_new_ticket_is_open_with_medium_priority_by_default(): void
    {
        $ticket = new Ticket;

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame('medium', $ticket->priority->value);
    }

    public function test_status_changes_keep_lifecycle_dates_in_sync(): void
    {
        $this->freezeSecond();
        $ticket = Ticket::factory()->inProgress()->create();

        $this->changeStatus($ticket, TicketStatus::Resolved);
        $this->assertTrue($ticket->resolved_at->equalTo(now()));
        $this->assertNull($ticket->closed_at);

        $this->changeStatus($ticket, TicketStatus::Closed);
        $this->assertTrue($ticket->closed_at->equalTo(now()));
        $this->assertNotNull($ticket->resolved_at);

        $this->changeStatus($ticket, TicketStatus::Open);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);
    }

    public function test_explicit_lifecycle_dates_are_preserved(): void
    {
        $resolvedAt = now()->subDays(3)->startOfSecond();

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => $resolvedAt,
        ]);

        $this->assertTrue($ticket->fresh()->resolved_at->equalTo($resolvedAt));
    }

    public function test_a_new_comment_updates_the_ticket_activity_date(): void
    {
        $ticket = Ticket::factory()->create(['updated_at' => now()->subDays(10)]);

        $this->travel(1)->minutes();
        Comment::factory()->for($ticket)->create();

        $this->assertTrue($ticket->fresh()->updated_at->equalTo(now()->startOfSecond()));
    }

    public function test_status_and_assignment_are_not_mass_assignable(): void
    {
        $this->expectException(MassAssignmentException::class);

        Ticket::factory()->create()->fill(['title' => 'New title', 'agent_id' => 999]);
    }

    private function changeStatus(Ticket $ticket, TicketStatus $status): void
    {
        $ticket->status = $status;
        $ticket->save();
    }
}
