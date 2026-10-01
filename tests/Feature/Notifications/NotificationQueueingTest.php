<?php

namespace Tests\Feature\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewCommentAdded;
use App\Notifications\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Real notification dispatcher, fake queue: proves e-mails go through queued jobs
 * instead of being sent during the HTTP request.
 */
class NotificationQueueingTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_pushed_on_the_queue(): void
    {
        Queue::fake();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)->patch(route('tickets.status', $ticket), ['status' => 'resolved']);
        $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), ['body' => 'Done']);

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof TicketStatusChanged
            && $job->notifiables->first()->is($ticket->client));
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof NewCommentAdded);
    }
}
