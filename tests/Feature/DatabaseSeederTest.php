<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_seeded(): void
    {
        $this->seed();

        $this->assertSame(UserRole::Admin, User::firstWhere('email', 'admin@laradesk.test')->role);
        $this->assertSame(UserRole::Agent, User::firstWhere('email', 'agent3@laradesk.test')->role);
        $this->assertSame(UserRole::Client, User::firstWhere('email', 'client@laradesk.test')->role);
        $this->assertSame(10, User::role(UserRole::Client)->count());
        $this->assertSame(5, Category::count());
        $this->assertSame(60, Ticket::count());
        $this->assertSame(0, Ticket::whereNull('reference')->count());
        $this->assertSame(0, Ticket::where('created_at', '<', now()->subDays(31))->count());
    }
}
