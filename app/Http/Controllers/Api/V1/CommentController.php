<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Ticket;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Ticket $ticket): CommentResource
    {
        $comment = $ticket->comments()->make([
            'body' => $request->validated('body'),
            'is_internal' => $request->boolean('is_internal'),
        ]);
        $comment->user()->associate($request->user());
        $comment->save();

        return CommentResource::make($comment);
    }
}
