<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to an agent when a ticket is assigned to them by someone else.
 */
class TicketAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
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
            ->subject(__('[:reference] A ticket was assigned to you', ['reference' => $this->ticket->reference]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The ticket ":title" is now assigned to you.', ['title' => $this->ticket->title]))
            ->line(__('Priority: :priority', ['priority' => $this->ticket->priority->label()]))
            ->action(__('View the ticket'), route('tickets.show', $this->ticket));
    }
}
