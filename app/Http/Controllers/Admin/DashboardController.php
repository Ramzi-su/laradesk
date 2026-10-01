<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Queries\TicketStatistics;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TicketStatistics $statistics): View
    {
        $createdPerDay = $statistics->createdPerDay(30);

        return view('admin.dashboard', [
            'byStatus' => $statistics->countByStatus(),
            'byPriority' => $statistics->countByPriority(),
            'agents' => $statistics->openTicketsPerAgent(),
            'unassigned' => $statistics->unassignedOpenTickets(),
            'averageResolutionHours' => $statistics->averageResolutionHours(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'chart' => [
                'labels' => array_map(fn (string $day) => Carbon::parse($day)->isoFormat('D MMM'), array_keys($createdPerDay)),
                'data' => array_values($createdPerDay),
                'label' => __('Tickets created'),
            ],
        ]);
    }
}
