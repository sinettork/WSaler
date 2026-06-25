<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function actingAsRole(string $role): string
    {
        $user = User::where('role', $role)->first();
        $token = $user->createToken('test')->plainTextToken;
        return $token;
    }

    public function test_admin_can_list_brands(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Brand::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/brands');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_cashier_can_list_brands(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        Brand::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/brands');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_brand(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/brands', [
                'name' => 'New Brand',
                'slug' => 'new-brand',
                'description' => 'Desc',
                'is_active' => true,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Brand');
        $this->assertDatabaseHas('brands', ['slug' => 'new-brand']);
    }

    public function test_cashier_cannot_create_brand(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/brands', [
                'name' => 'Bad',
                'slug' => 'bad-brand',
            ]);
        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/brands', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_admin_can_show_brand(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $brand = Brand::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/brands/'.$brand->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $brand->id);
    }

    public function test_admin_can_update_brand(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $brand = Brand::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/brands/'.$brand->id, [
                'name' => 'Updated Brand',
                'slug' => $brand->slug,
                'is_active' => false,
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Brand');
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Updated Brand']);
    }

    public function test_admin_can_delete_brand(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $brand = Brand::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/brands/'.$brand->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }
}
