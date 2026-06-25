<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_admin_can_list_customers(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Customer::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/customers');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_cashier_can_list_customers(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        Customer::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/customers');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_customer(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customers', [
                'name' => 'New Customer',
                'email' => 'customer@example.com',
                'type' => 'retail',
                'is_active' => true,
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Customer')
            ->assertJsonPath('data.code', 'CUST-00001');
        $this->assertDatabaseHas('customers', ['email' => 'customer@example.com']);
    }

    public function test_cashier_cannot_create_customer(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customers', [
                'name' => 'Bad',
                'email' => 'bad@example.com',
            ]);
        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customers', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_show_customer(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $customer = Customer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/customers/'.$customer->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $customer->id);
    }

    public function test_admin_can_update_customer(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $customer = Customer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/customers/'.$customer->id, [
                'name' => 'Updated Customer',
                'is_active' => false,
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Customer');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Customer']);
    }

    public function test_admin_can_delete_customer(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $customer = Customer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/customers/'.$customer->id);
        $response->assertStatus(204);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_type_filter_works(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        Customer::factory()->create(['type' => 'retail', 'code' => 'CUST-001']);
        Customer::factory()->create(['type' => 'wholesale', 'code' => 'CUST-002']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/customers?type=retail');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('retail', $data[0]['type']);
    }
}
