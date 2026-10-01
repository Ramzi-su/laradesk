<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\NewCommentAdded;
use Illuminate\Support\Facades\Notification;

class CommentObserver
{
    /**
     * Notifies the client and the assigned agent, except the author.
     * An internal note is never sent to the client.
     */
    public function created(Comment $comment): void
    {
        $ticket = $comment->ticket;

        $recipients = collect([
            $comment->is_internal ? null : $ticket->client,
            $ticket->agent,
        ])
            ->filter()
            ->reject(fn (User $user) => $user->is($comment->user))
            ->unique('id');

        Notification::send($recipients, new NewCommentAdded($comment));
    }
}
