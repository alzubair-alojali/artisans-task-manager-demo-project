<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the manager created in UserSeeder
        $manager = User::where('email', 'manager@example.com')->first();

        if (!$manager) {
            // Fallback if UserSeeder wasn't run or didn't create the manager
            $manager = User::factory()->create([
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'role' => UserRole::MANAGER,
            ]);
            $manager->assignRole(UserRole::MANAGER->value);
        }

        // Get some users to be members
        $users = User::where('role', UserRole::USER)->get();

        if ($users->isEmpty()) {
            $users = User::factory(5)->create([
                'role' => UserRole::USER,
            ]);
            foreach ($users as $user) {
                $user->assignRole(UserRole::USER->value);
            }
        }

        // Create Projects
        $projects = [
            [
                'title' => 'Website Redesign',
                'description' => 'Overhaul the company website with new branding.',
                'deadline' => now()->addMonths(2),
                'status' => ProjectStatus::OPEN,
                'manager_id' => $manager->id,
            ],
            [
                'title' => 'Mobile App Development',
                'description' => 'Develop a cross-platform mobile app for customers.',
                'deadline' => now()->addMonths(4),
                'status' => ProjectStatus::OPEN,
                'manager_id' => $manager->id,
            ],
            [
                'title' => 'Legacy System Migration',
                'description' => 'Migrate legacy data to the new cloud infrastructure.',
                'deadline' => now()->subWeek(), // Past deadline
                'status' => ProjectStatus::COMPLETED,
                'manager_id' => $manager->id,
            ],
            [
                'title' => 'Internal Dashboard',
                'description' => 'Create a dashboard for internal metrics tracking.',
                'deadline' => now()->addMonth(),
                'status' => ProjectStatus::ARCHIVED,
                'manager_id' => $manager->id,
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::create($projectData);

            // Assign random members to the project
            $project->members()->attach($users->random(min(3, $users->count()))->pluck('id'));
        }
    }
}
