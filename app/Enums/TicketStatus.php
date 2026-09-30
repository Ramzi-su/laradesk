<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __("enums.ticket_status.{$this->value}");
    }

    /**
     * Statuses of tickets that still need work from an agent.
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [self::Open, self::InProgress];
    }
}
