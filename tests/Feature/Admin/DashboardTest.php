<?php

namespace Tests\Feature\Admin;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Queries\TicketStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_the_dashboard(): void
    {
        $this->actingAs(User::factory()->client()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('tickets-per-day');
    }

    public function test_counts_by_status_and_priority_include_empty_values_and_skip_deleted_tickets(): void
    {
        Ticket::factory()->count(2)->priority(TicketPriority::Urgent)->create();
        Ticket::factory()->inProgress()->priority(TicketPriority::Low)->create();
        Ticket::factory()->priority(TicketPriority::Low)->create()->delete();

        $statistics = new TicketStatistics;

        $this->assertSame(['open' => 2, 'in_progress' => 1, 'resolved' => 0, 'closed' => 0], $statistics->countByStatus());
        $this->assertSame(['low' => 1, 'medium' => 0, 'high' => 0, 'urgent' => 2], $statistics->countByPriority());
    }

    public function test_open_tickets_per_agent(): void
    {
        $busy = User::factory()->agent()->create(['name' => 'Busy']);
        $idle = User::factory()->agent()->create(['name' => 'Idle']);
        Ticket::factory()->count(2)->assignedTo($busy)->create();
        Ticket::factory()->inProgress()->assignedTo($busy)->create();
        Ticket::factory()->closed()->assignedTo($idle)->create();
        Ticket::factory()->count(2)->create();

        $statistics = new TicketStatistics;

        $this->assertSame(['Busy' => 3, 'Idle' => 0], $statistics->openTicketsPerAgent()->pluck('open_tickets_count', 'name')->all());
        $this->assertSame(2, $statistics->unassignedOpenTickets());
    }

    public function test_average_resolution_time_in_hours(): void
    {
        $this->assertNull((new TicketStatistics)->averageResolutionHours());

        $created = now()->subDays(3);
        Ticket::factory()->resolved()->create(['created_at' => $created, 'resolved_at' => $created->copy()->addHours(10)]);
        Ticket::factory()->closed()->create(['created_at' => $created, 'resolved_at' => $created->copy()->addHours(21)]);
        Ticket::factory()->inProgress()->create(['created_at' => $created]);

        $this->assertSame(15.5, (new TicketStatistics)->averageResolutionHours());
    }

    public function test_tickets_created_per_day_covers_30_days_with_zeros(): void
    {
        $this->freezeTime();
        Ticket::factory()->count(2)->create(['created_at' => now()]);
        Ticket::factory()->create(['created_at' => now()->subDays(29)]);
        Ticket::factory()->create(['created_at' => now()->subDays(30)]);

        $perDay = (new TicketStatistics)->createdPerDay(30);

        $this->assertCount(30, $perDay);
        $this->assertSame(2, $perDay[now()->toDateString()]);
        $this->assertSame(1, $perDay[now()->subDays(29)->toDateString()]);
        $this->assertSame(3, array_sum($perDay));
    }

    public function test_the_number_of_queries_does_not_grow_with_the_number_of_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->agent()->count(3)->create();

        $countQueries = function () use ($admin): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

            return count(DB::getQueryLog());
        };

        Ticket::factory()->count(2)->assignedTo()->create();
        $few = $countQueries();

        Ticket::factory()->count(40)->assignedTo()->create();
        $many = $countQueries();

        $this->assertSame($few, $many);
    }
}
