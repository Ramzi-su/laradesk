<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'priority' => ['value' => $this->priority->value, 'label' => $this->priority->label()],
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'client' => UserResource::make($this->whenLoaded('client')),
            'agent' => UserResource::make($this->whenLoaded('agent')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            // Defense in depth: the controller already filters with Comment::visibleTo(),
            // the resource drops internal notes again for non-staff users.
            'comments' => CommentResource::collection($this->whenLoaded(
                'comments',
                fn () => $request->user()->isStaff()
                    ? $this->comments
                    : $this->comments->reject(fn (Comment $comment) => $comment->is_internal)->values(),
            )),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
