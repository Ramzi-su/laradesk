<?php

namespace App\Queries;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Aggregated figures for the admin dashboard. Every method runs a single
 * GROUP BY / aggregate query: tickets are never loaded into memory.
 * Soft-deleted tickets are excluded by the SoftDeletes global scope.
 */
class TicketStatistics
{
    /**
     * Number of tickets per status, every status present (0 when none).
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        return $this->countBy('status', TicketStatus::cases());
    }

    /**
     * @return array<string, int>
     */
    public function countByPriority(): array
    {
        return $this->countBy('priority', TicketPriority::cases());
    }

    /**
     * Agents with the number of tickets that still need work (open + in progress).
     *
     * @return Collection<int, User>
     */
    public function openTicketsPerAgent(): Collection
    {
        return User::role(UserRole::Agent)
            ->withCount(['assignedTickets as open_tickets_count' => fn ($query) => $query->open()])
            ->orderByDesc('open_tickets_count')
            ->orderBy('name')
            ->get();
    }

    public function unassignedOpenTickets(): int
    {
        return Ticket::open()->whereNull('agent_id')->count();
    }

    /**
     * Average time between creation and resolution, in hours (null without resolved tickets).
     */
    public function averageResolutionHours(): ?float
    {
        $seconds = Ticket::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) AS average_seconds')
            ->value('average_seconds');

        return $seconds === null ? null : round($seconds / 3600, 1);
    }

    /**
     * Tickets created per day over the last N days, today included, with empty days set to 0.
     *
     * @return array<string, int> keyed by Y-m-d
     */
    public function createdPerDay(int $days = 30): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $counts = Ticket::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(CarbonPeriod::create($from, Carbon::today()))
            ->mapWithKeys(fn (Carbon $day) => [$day->toDateString() => (int) ($counts[$day->toDateString()] ?? 0)])
            ->all();
    }

    /**
     * $column is always a hard-coded column name from this class, never user input,
     * which is what makes interpolating it in selectRaw() safe.
     *
     * @param  'status'|'priority'  $column
     * @param  array<int, TicketStatus|TicketPriority>  $cases
     * @return array<string, int>
     */
    private function countBy(string $column, array $cases): array
    {
        $counts = Ticket::query()
            ->selectRaw("{$column}, COUNT(*) AS total")
            ->groupBy($column)
            ->toBase()
            ->pluck('total', $column);

        return collect($cases)
            ->mapWithKeys(fn ($case) => [$case->value => (int) ($counts[$case->value] ?? 0)])
            ->all();
    }
}
