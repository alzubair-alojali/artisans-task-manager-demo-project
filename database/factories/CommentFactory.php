<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraph(),
            'user_id' => User::factory(), // Default, but usually overridden
        ];
    }

    public function forTask($task)
    {
        return $this->state(function (array $attributes) use ($task) {
            return [
                'commentable_id' => $task->id,
                'commentable_type' => \App\Models\Task::class,
            ];
        });
    }

    public function forProject($project)
    {
        return $this->state(function (array $attributes) use ($project) {
            return [
                'commentable_id' => $project->id,
                'commentable_type' => \App\Models\Project::class,
            ];
        });
    }
}
