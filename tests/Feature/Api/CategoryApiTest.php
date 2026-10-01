<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_can_list_categories(): void
    {
        Category::factory()->create(['name' => 'Billing']);
        Category::factory()->create(['name' => 'Account']);
        Sanctum::actingAs(User::factory()->client()->create());

        $this->getJson(route('api.v1.categories.index'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Account')
            ->assertJsonPath('data.1.name', 'Billing')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'description']], 'meta']);
    }

    public function test_guests_cannot_list_categories(): void
    {
        $this->getJson(route('api.v1.categories.index'))->assertUnauthorized();
    }
}
