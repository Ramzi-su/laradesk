<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tickets:close-stale {--days=7 : Days without activity before a resolved ticket is closed}')]
#[Description('Close resolved tickets without activity for a given number of days')]
class CloseStaleTickets extends Command
{
    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            $this->error(__('The --days option must be a positive integer.'));

            return self::INVALID;
        }

        $closed = 0;

        // Each ticket is saved through Eloquent (not a mass UPDATE) so the observer
        // sets closed_at and notifies the client. lazyById() keeps memory flat.
        Ticket::status(TicketStatus::Resolved)
            ->where('updated_at', '<', now()->subDays($days))
            ->with('client')
            ->lazyById()
            ->each(function (Ticket $ticket) use (&$closed) {
                $ticket->status = TicketStatus::Closed;
                $ticket->save();
                $closed++;
            });

        $this->info(trans_choice(':count stale ticket(s) closed.', $closed, ['count' => $closed]));

        return self::SUCCESS;
    }
}
