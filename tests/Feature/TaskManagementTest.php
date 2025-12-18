<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskManagementTest extends TestCase
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

    public function test_manager_can_create_task_in_project(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $project->members()->attach($manager);

        $taskData = [
            'title' => 'New Feature Task',
            'description' => 'Implement the new feature.',
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::HIGH->value,
            'project_id' => $project->id,
            'assigned_to' => $manager->id,
        ];

        $response = $this->actingAs($manager)->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Feature Task')
            ->assertJsonPath('data.status', TaskStatus::TODO->value)
            ->assertJsonPath('data.priority', TaskPriority::HIGH->value);
    }

    public function test_regular_user_cannot_create_task(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $project = Project::factory()->create();

        $taskData = [
            'title' => 'Unauthorized Task',
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
            'project_id' => $project->id,
        ];

        $response = $this->actingAs($user)->postJson('/api/tasks', $taskData);

        $response->assertStatus(403);
    }

    public function test_user_sees_only_relevant_tasks(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        // Project A: User is a member
        $projectA = Project::factory()->create();
        $projectA->members()->attach($user);
        $taskA = Task::factory()->create(['project_id' => $projectA->id, 'title' => 'Task A']);

        // Project B: User is NOT a member
        $projectB = Project::factory()->create();
        $taskB = Task::factory()->create(['project_id' => $projectB->id, 'title' => 'Task B']);

        $response = $this->actingAs($user)->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Task A'])
            ->assertJsonMissing(['title' => 'Task B']);
    }

    public function test_filtering_tasks_by_status_and_priority(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create(['manager_id' => $manager->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::TODO,
            'priority' => TaskPriority::HIGH,
            'title' => 'High Priority Todo'
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::DONE,
            'priority' => TaskPriority::LOW,
            'title' => 'Low Priority Done'
        ]);

        $response = $this->actingAs($manager)->getJson('/api/tasks?filter[status]=todo&filter[priority]=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'High Priority Todo'])
            ->assertJsonMissing(['title' => 'Low Priority Done']);
    }

    public function test_manager_can_delete_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        // Manager must be the project's manager to delete tasks
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_can_filter_trashed_tasks(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        $project = Project::factory()->create(['manager_id' => $manager->id]);

        $activeTask = Task::factory()->create(['project_id' => $project->id, 'title' => 'Active Task']);
        $trashedTask = Task::factory()->create(['project_id' => $project->id, 'title' => 'Trashed Task']);
        $trashedTask->delete();

        // Test filter[trashed]=only
        $response = $this->actingAs($manager)->getJson('/api/tasks?filter[trashed]=only');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Trashed Task'])
            ->assertJsonMissing(['title' => 'Active Task']);

        // Test filter[trashed]=with
        $response = $this->actingAs($manager)->getJson('/api/tasks?filter[trashed]=with');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Trashed Task'])
            ->assertJsonFragment(['title' => 'Active Task']);
    }

    public function test_manager_can_restore_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        // Manager must be the project's manager to restore tasks
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $task->delete();

        $response = $this->actingAs($manager)->postJson("/api/tasks/{$task->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);

        $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_manager_can_force_delete_task(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $manager->assignRole(UserRole::MANAGER);

        // Manager must be the project's manager to force delete tasks
        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $task->delete();

        $response = $this->actingAs($manager)->deleteJson("/api/tasks/{$task->id}/force-delete");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_regular_user_cannot_restore_or_force_delete_task(): void
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $user->assignRole(UserRole::USER);

        $task = Task::factory()->create();
        $task->delete();

        // Try restore
        $response = $this->actingAs($user)->postJson("/api/tasks/{$task->id}/restore");
        $response->assertStatus(403);

        // Try force delete
        $response = $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}/force-delete");
        $response->assertStatus(403);
    }
}
