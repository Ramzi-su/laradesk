<?php

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the client when the status of their ticket changes.
 */
class TicketStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketStatus $previousStatus,
    ) {
        // Only queue once the surrounding database transaction (if any) has committed.
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('[:reference] Status changed: :status', [
                'reference' => $this->ticket->reference,
                'status' => $this->ticket->status->label(),
            ]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The status of your ticket ":title" changed from ":from" to ":to".', [
                'title' => $this->ticket->title,
                'from' => $this->previousStatus->label(),
                'to' => $this->ticket->status->label(),
            ]))
            ->when($this->ticket->status === TicketStatus::Resolved, fn (MailMessage $mail) => $mail
                ->line(__('If the problem is solved, you can close the ticket. Otherwise, just reply in a comment.')))
            ->action(__('View the ticket'), route('tickets.show', $this->ticket));
    }
}
