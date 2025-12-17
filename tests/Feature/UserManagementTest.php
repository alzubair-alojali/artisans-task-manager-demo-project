<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        foreach (UserRole::cases() as $role) {
            Role::create(['name' => $role->value]);
        }
    }

    public function test_admin_can_view_all_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN);

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'created_at']
                ]
            ]);
    }

    public function test_regular_user_cannot_view_users(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $response = $this->actingAs($user)->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::USER->value,
        ];

        $response = $this->actingAs($admin)->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'newuser@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => UserRole::USER->value,
        ]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN);

        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::MANAGER->value,
        ];

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.role', UserRole::MANAGER->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::MANAGER->value,
        ]);

        // Verify Spatie role sync
        $this->assertTrue($user->fresh()->hasRole(UserRole::MANAGER->value));
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_filter_users_by_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN);

        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $response = $this->actingAs($admin)->getJson('/api/users?filter[role]=manager');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $manager->email);
    }
}
