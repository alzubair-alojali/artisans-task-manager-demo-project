<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
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

    public function test_regular_user_cannot_create_project(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $projectData = [
            'title' => 'New Project',
            'description' => 'Description',
            'deadline' => now()->addDays(5)->format('Y-m-d'),
            'status' => ProjectStatus::OPEN->value,
        ];

        $response = $this->actingAs($user)->postJson('/api/projects', $projectData);

        $response->assertStatus(403);
    }

    public function test_manager_can_create_project(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $projectData = [
            'title' => 'Manager Project',
            'description' => 'Description',
            'deadline' => now()->addDays(5)->format('Y-m-d'),
            'status' => ProjectStatus::OPEN->value,
        ];

        $response = $this->actingAs($manager)->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Manager Project')
            ->assertJsonPath('data.manager.id', $manager->id);
    }

    public function test_cannot_create_project_with_past_deadline(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $projectData = [
            'title' => 'Past Project',
            'description' => 'Description',
            'deadline' => now()->subDay()->format('Y-m-d'),
            'status' => ProjectStatus::OPEN->value,
        ];

        $response = $this->actingAs($manager)->postJson('/api/projects', $projectData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_project_response_uses_standard_json_resource(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'status' => ProjectStatus::OPEN,
        ]);

        $response = $this->actingAs($manager)->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'manager' => [
                        'id',
                        'name',
                    ],
                    'members_count',
                ]
            ])
            ->assertJsonPath('data.status', ProjectStatus::OPEN->value);
    }

    public function test_manager_can_update_own_project(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'status' => ProjectStatus::OPEN,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => ProjectStatus::COMPLETED->value,
        ];

        $response = $this->actingAs($manager)->putJson("/api/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', ProjectStatus::COMPLETED->value);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
            'status' => ProjectStatus::COMPLETED->value,
        ]);
    }

    public function test_manager_can_delete_own_project(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create([
            'manager_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_can_filter_projects_by_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        Project::factory()->create(['status' => ProjectStatus::OPEN]);
        Project::factory()->create(['status' => ProjectStatus::COMPLETED]);

        $response = $this->actingAs($user)->getJson('/api/projects?filter[status]=open');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', ProjectStatus::OPEN->value);
    }
}
