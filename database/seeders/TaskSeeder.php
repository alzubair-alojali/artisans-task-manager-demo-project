<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::with('members')->get();
        $users = User::all();

        if ($projects->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            // Create 5-10 tasks for each project
            $count = rand(5, 10);

            for ($i = 0; $i < $count; $i++) {
                // Determine assignee: prefer project member, fallback to random user, or null
                $assignee = null;
                if ($project->members->isNotEmpty()) {
                    $assignee = $project->members->random();
                } elseif ($users->isNotEmpty()) {
                    // Fallback if no members assigned yet
                    $assignee = $users->random();
                }

                Task::create([
                    'title' => fake()->sentence(4),
                    'description' => fake()->paragraph(),
                    'due_date' => fake()->dateTimeBetween('-1 month', '+2 months'),
                    'status' => fake()->randomElement(TaskStatus::cases()),
                    'priority' => fake()->randomElement(TaskPriority::cases()),
                    'project_id' => $project->id,
                    'assigned_to' => $assignee?->id,
                    'created_by' => $project->manager_id ?? $users->random()->id,
                ]);
            }
        }
    }
}
