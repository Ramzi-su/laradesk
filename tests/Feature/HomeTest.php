<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_the_ticket_list(): void
    {
        $this->get('/')->assertRedirect('/tickets');
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/tickets')->assertRedirect(route('login'));
    }

    public function test_dashboard_redirects_to_the_ticket_list(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/tickets');
    }
}
