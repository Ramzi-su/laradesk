<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_category_management(): void
    {
        $this->actingAs(User::factory()->client()->create())->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.categories.index'))->assertOk();
    }

    public function test_non_admin_cannot_create_a_category(): void
    {
        $this->actingAs(User::factory()->agent()->create())
            ->post(route('admin.categories.store'), ['name' => 'Hack'])
            ->assertForbidden();

        $this->assertSame(0, Category::count());
    }

    public function test_admin_can_create_a_category_with_a_generated_slug(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), ['name' => 'Après-vente', 'description' => 'SAV'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Après-vente', 'slug' => 'apres-vente']);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), ['name' => 'Billing'])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_a_category_keeping_its_name(): void
    {
        $category = Category::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.categories.update', $category), ['name' => 'Billing', 'description' => 'Updated'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated', $category->fresh()->description);
    }

    public function test_admin_can_delete_an_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.categories.destroy', $category));

        $this->assertModelMissing($category);
    }

    public function test_a_category_with_tickets_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Ticket::factory()->for($category)->create()->delete(); // even a soft-deleted ticket counts

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }
}
