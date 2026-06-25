<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_manager_can_list_users(): void
    {
        $token = $this->actingAsRole(UserRole::Manager->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_cashier_cannot_list_users(): void
    {
        $token = $this->actingAsRole(UserRole::Cashier->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'cashier',
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'newuser@example.com');
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $token = $this->actingAsRole(UserRole::Manager->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'newuser2@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'cashier',
            ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_update_user(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $target = User::where('email', 'cashier@example.com')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/users/'.$target->id, [
                'name' => 'Updated Cashier',
                'email' => $target->email,
                'role' => 'cashier',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Cashier');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(422);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $target = User::where('email', 'cashier@example.com')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/users/'.$target->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_email_validation_works(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'Bad Email',
                'email' => 'not-an-email',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'cashier',
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_role_validation_works(): void
    {
        $token = $this->actingAsRole(UserRole::Administrator->value);
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users', [
                'name' => 'Bad Role',
                'email' => 'badrole@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'invalid_role',
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }
}
