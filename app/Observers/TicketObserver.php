<?php

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\Ticket;

class TicketObserver
{
    /**
     * Keeps resolved_at / closed_at consistent with the status.
     * Timestamps already set explicitly (e.g. by the seeder) are preserved.
     */
    public function saving(Ticket $ticket): void
    {
        if (! $ticket->isDirty('status')) {
            return;
        }

        match ($ticket->status) {
            TicketStatus::Open, TicketStatus::InProgress => $ticket->forceFill([
                'resolved_at' => null,
                'closed_at' => null,
            ]),
            TicketStatus::Resolved => $ticket->forceFill([
                'resolved_at' => $ticket->resolved_at ?? now(),
                'closed_at' => null,
            ]),
            TicketStatus::Closed => $ticket->forceFill([
                'closed_at' => $ticket->closed_at ?? now(),
            ]),
        };
    }

    /**
     * Derives the public reference (LD-000123) from the auto-increment id.
     * The id only exists after the insert, so this cannot happen in "creating";
     * computing "max + 1" beforehand would race under concurrent requests.
     */
    public function created(Ticket $ticket): void
    {
        $ticket->reference = sprintf('LD-%06d', $ticket->getKey());

        // Quiet (no events) and without touching updated_at, which tracks real activity.
        Ticket::withoutTimestamps(fn () => $ticket->saveQuietly());
    }
}
