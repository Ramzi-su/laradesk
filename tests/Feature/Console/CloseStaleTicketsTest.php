<?php

namespace Tests\Feature\Console;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\TicketStatusChanged;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CloseStaleTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_only_resolved_tickets_without_recent_activity(): void
    {
        Notification::fake();
        $stale = Ticket::factory()->resolved()->create(['updated_at' => now()->subDays(8)]);
        $recent = Ticket::factory()->resolved()->create(['updated_at' => now()->subDays(6)]);
        $oldInProgress = Ticket::factory()->inProgress()->create(['updated_at' => now()->subDays(30)]);
        $alreadyClosed = Ticket::factory()->closed()->create(['updated_at' => now()->subDays(30)]);

        $this->artisan('tickets:close-stale')
            ->expectsOutput('1 ticket fermé.')
            ->assertSuccessful();

        $this->assertSame(TicketStatus::Closed, $stale->fresh()->status);
        $this->assertNotNull($stale->fresh()->closed_at);
        $this->assertSame(TicketStatus::Resolved, $recent->fresh()->status);
        $this->assertSame(TicketStatus::InProgress, $oldInProgress->fresh()->status);
        $this->assertTrue($alreadyClosed->fresh()->updated_at->lessThan(now()->subDays(29)));

        Notification::assertSentTo($stale->client, TicketStatusChanged::class);
        Notification::assertNotSentTo($recent->client, TicketStatusChanged::class);
    }

    public function test_a_recent_comment_counts_as_activity(): void
    {
        $ticket = Ticket::factory()->resolved()->create(['updated_at' => now()->subDays(10)]);
        $ticket->comments()->make(['body' => 'Still broken'])->user()->associate($ticket->client_id)->save();

        $this->artisan('tickets:close-stale')->expectsOutput('Aucun ticket à fermer.');

        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
    }

    public function test_the_delay_can_be_configured(): void
    {
        $ticket = Ticket::factory()->resolved()->create(['updated_at' => now()->subDays(3)]);

        $this->artisan('tickets:close-stale', ['--days' => 2])->expectsOutput('1 ticket fermé.');

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_invalid_days_option_is_rejected(): void
    {
        $this->artisan('tickets:close-stale', ['--days' => '0'])->assertFailed();
        $this->artisan('tickets:close-stale', ['--days' => 'abc'])->assertFailed();
    }

    public function test_it_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains($event->command, 'tickets:close-stale'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
