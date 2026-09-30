<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every page once per relevant role, so a broken view or an
 * N+1 query (strict mode) fails the suite.
 */
class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_pages_render(): void
    {
        $ticket = Ticket::factory()->resolved()->assignedTo()->has(Attachment::factory()->count(2))->create();
        Comment::factory()->count(3)->for($ticket)->create();
        Ticket::factory()->count(3)->for($ticket->client, 'client')->create();

        $this->actingAs($ticket->client);

        $this->get(route('tickets.index'))->assertOk()->assertSee(__('New ticket'));
        $this->get(route('tickets.create'))->assertOk();
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee(__('Close the ticket'));
        $this->get(route('tickets.edit', Ticket::factory()->for($ticket->client, 'client')->create()))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_agent_pages_render(): void
    {
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->assignedTo($agent)->create();
        Comment::factory()->count(2)->for($ticket)->internal()->create();
        $unassigned = Ticket::factory()->create();

        $this->actingAs($agent);

        $this->get(route('tickets.index'))->assertOk()->assertDontSee(__('New ticket'));
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee(__('Internal note (hidden from the client)'));
        $this->get(route('tickets.show', $unassigned))->assertOk()->assertSee(__('Take this ticket'));
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->agent()->count(2)->create();
        $category = Category::factory()->create();
        Ticket::factory()->count(3)->for($category)->assignedTo()->create();

        $this->actingAs($admin);

        $this->get(route('tickets.index'))->assertOk();
        $this->get(route('tickets.show', Ticket::first()))->assertOk()->assertSee(__('Delete the ticket'));
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.categories.create'))->assertOk();
        $this->get(route('admin.categories.edit', $category))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.users.index', ['role' => 'agent']))->assertOk();
    }
}
