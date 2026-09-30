<?php

namespace Tests\Feature\Admin;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_list_users(): void
    {
        $this->actingAs(User::factory()->agent()->create())->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_can_change_a_user_role(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.users.role', $user), ['role' => 'agent'])
            ->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Agent, $user->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin), ['role' => 'client'])
            ->assertForbidden();

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_non_admin_cannot_change_a_role(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->patch(route('admin.users.role', $agent), ['role' => 'admin'])
            ->assertForbidden();
    }

    public function test_demoting_an_agent_releases_their_active_tickets_only(): void
    {
        $agent = User::factory()->agent()->create();
        $active = Ticket::factory()->inProgress()->assignedTo($agent)->create();
        $closed = Ticket::factory()->closed()->assignedTo($agent)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.users.role', $agent), ['role' => 'client']);

        $this->assertNull($active->fresh()->agent_id);
        $this->assertSame(TicketStatus::InProgress, $active->fresh()->status);
        $this->assertSame($agent->id, $closed->fresh()->agent_id);
    }
}
