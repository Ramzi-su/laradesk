<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the other party of a ticket when a comment is added.
 * Recipients are chosen in CommentObserver: an internal note never reaches the client.
 */
class NewCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment)
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
        $ticket = $this->comment->ticket;

        return (new MailMessage)
            ->subject(__('[:reference] New comment', ['reference' => $ticket->reference]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':author commented on the ticket ":title":', [
                'author' => $this->comment->user->name,
                'title' => $ticket->title,
            ]))
            ->line(Str::limit($this->comment->body, 300))
            ->action(__('Reply'), route('tickets.show', $ticket).'#comments');
    }
}
