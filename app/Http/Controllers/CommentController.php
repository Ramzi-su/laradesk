<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        // ticket_id comes from the route and user_id from the session, never from the payload.
        $comment = $ticket->comments()->make([
            'body' => $request->validated('body'),
            'is_internal' => $request->boolean('is_internal'),
        ]);
        $comment->user()->associate($request->user());
        $comment->save();

        return to_route('tickets.show', $ticket)
            ->withFragment('comments')
            ->with('success', __('Comment added.'));
    }
}
