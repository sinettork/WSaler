<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RolePermissionSeeder::class]);
    }

    protected function actingAsRole(string $role): string
    {
        $user = User::factory()->create(['role' => $role]);
        $spatieRole = match ($role) {
            'admin' => 'administrator',
            'manager' => 'manager',
            'cashier' => 'cashier',
            'warehouse' => 'warehouse_staff',
            'purchasing' => 'purchasing_staff',
            'delivery' => 'delivery_staff',
            'salesperson' => 'sales_staff',
            default => 'cashier',
        };
        $user->assignRole($spatieRole);
        return $user->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_categories(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Category::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_cashier_can_list_categories(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        Category::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/categories');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_category(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/categories', [
                'name' => 'New Category',
                'slug' => 'new-category',
                'description' => 'Desc',
                'parent_id' => null,
                'is_active' => true,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Category');
        $this->assertDatabaseHas('categories', ['slug' => 'new-category']);
    }

    public function test_cashier_cannot_create_category(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/categories', [
                'name' => 'Bad',
                'slug' => 'bad-category',
            ]);
        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/categories', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_show_category(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/categories/'.$category->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_admin_can_update_category(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/categories/'.$category->id, [
                'name' => 'Updated Name',
                'slug' => $category->slug,
                'is_active' => false,
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_category(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/categories/'.$category->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_search_filters_name(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Beta']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/categories?search=Alpha');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alpha', $data[0]['name']);
    }
}
