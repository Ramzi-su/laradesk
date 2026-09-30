<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketListTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_only_sees_their_tickets(): void
    {
        $client = User::factory()->client()->create();
        $mine = Ticket::factory()->for($client, 'client')->create(['title' => 'My printer']);
        $other = Ticket::factory()->create(['title' => 'Someone else problem']);

        $this->actingAs($client)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertDontSee($other->title);
    }

    public function test_filters_are_applied_and_kept_in_pagination_links(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Ticket::factory()->count(16)->for($category)->priority(TicketPriority::Urgent)->create();
        Ticket::factory()->priority(TicketPriority::Low)->create(['title' => 'Low priority ticket']);

        $response = $this->actingAs($admin)->get(route('tickets.index', ['priority' => 'urgent', 'category' => $category->id]));

        $response->assertOk()->assertDontSee('Low priority ticket');
        $this->assertSame(16, $response->viewData('tickets')->total());
        $this->assertStringContainsString('priority=urgent', $response->viewData('tickets')->nextPageUrl());
    }

    public function test_search_filter(): void
    {
        $admin = User::factory()->admin()->create();
        Ticket::factory()->create(['title' => 'Invoice missing']);
        Ticket::factory()->create(['title' => 'Parcel lost']);

        $this->actingAs($admin)->get(route('tickets.index', ['search' => 'invoice']))
            ->assertSee('Invoice missing')
            ->assertDontSee('Parcel lost');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tickets.index', ['status' => 'hacked']))
            ->assertSessionHasErrors('status');
    }
}
