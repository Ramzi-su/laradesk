<?php

namespace Tests\Feature\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewCommentAdded;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_all_notifications_are_queued(): void
    {
        foreach ([TicketStatusChanged::class, TicketAssigned::class, NewCommentAdded::class] as $notification) {
            $this->assertContains(ShouldQueue::class, class_implements($notification));
        }
    }

    public function test_client_is_notified_when_an_agent_changes_the_status(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)->patch(route('tickets.status', $ticket), ['status' => 'resolved']);

        Notification::assertSentTo(
            $ticket->client,
            TicketStatusChanged::class,
            fn (TicketStatusChanged $notification) => $notification->previousStatus === TicketStatus::Open
                && $notification->ticket->is($ticket),
        );
        Notification::assertNotSentTo($agent, TicketStatusChanged::class);
    }

    public function test_status_change_through_the_api_notifies_too(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();
        Sanctum::actingAs($agent);

        $this->patchJson(route('api.v1.tickets.status', $ticket), ['status' => 'in_progress'])->assertOk();

        Notification::assertSentTo($ticket->client, TicketStatusChanged::class);
    }

    public function test_client_is_not_notified_when_closing_their_own_ticket(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs($ticket->client)->patch(route('tickets.status', $ticket), ['status' => 'closed']);

        Notification::assertNothingSent();
    }

    public function test_agent_is_notified_when_an_admin_assigns_them(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('tickets.assignment', $ticket), ['agent_id' => $agent->id]);

        Notification::assertSentTo($agent, TicketAssigned::class);
    }

    public function test_agent_is_not_notified_when_taking_a_ticket_themselves(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)->patch(route('tickets.assignment', $ticket), ['agent_id' => $agent->id]);

        Notification::assertNotSentTo($agent, TicketAssigned::class);
    }

    public function test_client_comment_notifies_the_assigned_agent_only(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($ticket->client)->post(route('tickets.comments.store', $ticket), ['body' => 'Any news?']);

        Notification::assertSentTo($agent, NewCommentAdded::class);
        Notification::assertNotSentTo($ticket->client, NewCommentAdded::class);
    }

    public function test_agent_public_comment_notifies_the_client(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), ['body' => 'Fixed!']);

        Notification::assertSentTo($ticket->client, NewCommentAdded::class);
        Notification::assertNotSentTo($agent, NewCommentAdded::class);
    }

    public function test_internal_comment_is_never_sent_to_the_client(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Staff only', 'is_internal' => '1']);

        Notification::assertNotSentTo($ticket->client, NewCommentAdded::class);
        Notification::assertSentTo($agent, NewCommentAdded::class);
    }

    public function test_comment_on_an_unassigned_ticket_by_its_client_notifies_nobody(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->client)->post(route('tickets.comments.store', $ticket), ['body' => 'Hello?']);

        Notification::assertNothingSent();
    }

    public function test_status_mail_is_in_french_and_links_to_the_ticket(): void
    {
        $ticket = Ticket::factory()->resolved()->create(['title' => 'Imprimante en panne']);

        $mail = (new TicketStatusChanged($ticket, TicketStatus::InProgress))->toMail($ticket->client);

        $this->assertSame("[{$ticket->reference}] Statut modifié : Résolu", $mail->subject);
        $this->assertStringContainsString('« Imprimante en panne » est passé de « En cours » à « Résolu »', implode(' ', $mail->introLines));
        $this->assertSame(route('tickets.show', $ticket), $mail->actionUrl);
    }
}
