<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
