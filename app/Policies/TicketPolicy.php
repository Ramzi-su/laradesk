<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Admins can do everything on tickets.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Everyone has a ticket list; Ticket::forUser() decides what is in it.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return match (true) {
            $user->isClient() => $this->owns($user, $ticket),
            $user->isAgent() => $ticket->agent_id === null || $this->handles($user, $ticket),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Title and description can only be edited while nobody works on the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $this->owns($user, $ticket) && $ticket->status === TicketStatus::Open;
    }

    /**
     * A client may only close their own resolved ticket (the allowed target
     * status is enforced by UpdateTicketStatusRequest).
     */
    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return match (true) {
            $user->isClient() => $this->owns($user, $ticket) && $ticket->status === TicketStatus::Resolved,
            $user->isAgent() => $this->handles($user, $ticket),
            default => false,
        };
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $this->handles($user, $ticket);
    }

    /**
     * Agents can only pick up unassigned tickets (for themselves, see AssignTicketRequest).
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $ticket->agent_id === null;
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->owns($user, $ticket) || $this->handles($user, $ticket);
    }

    public function commentInternally(User $user, Ticket $ticket): bool
    {
        return $this->handles($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    private function owns(User $user, Ticket $ticket): bool
    {
        return $user->isClient() && $ticket->client_id === $user->id;
    }

    private function handles(User $user, Ticket $ticket): bool
    {
        return $user->isAgent() && $ticket->agent_id === $user->id;
    }
}
