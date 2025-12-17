<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Comments for Projects
        $projects = Project::with('members')->get();

        foreach ($projects as $project) {
            if ($project->members->isEmpty()) {
                continue;
            }

            // Create 1-3 comments per project
            for ($i = 0; $i < rand(1, 3); $i++) {
                Comment::factory()->create([
                    'user_id' => $project->members->random()->id,
                    'commentable_id' => $project->id,
                    'commentable_type' => Project::class,
                ]);
            }
        }

        // Seed Comments for Tasks
        $tasks = Task::with('project.members')->get();

        foreach ($tasks as $task) {
            $project = $task->project;
            if (!$project || $project->members->isEmpty()) {
                continue;
            }

            // Create 1-3 comments per task
            for ($i = 0; $i < rand(1, 3); $i++) {
                Comment::factory()->create([
                    'user_id' => $project->members->random()->id,
                    'commentable_id' => $task->id,
                    'commentable_type' => Task::class,
                ]);
            }
        }
    }
}
