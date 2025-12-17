<?php

namespace Database\Seeders;

use App\Enums\roleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        foreach (roleEnum::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }

        // 2. Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => roleEnum::ADMIN,
            ]
        );
        $admin->assignRole(roleEnum::ADMIN->value);

        // 3. Create Manager User
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager User',
                'password' => bcrypt('password'),
                'role' => roleEnum::MANAGER,
            ]
        );
        $manager->assignRole(roleEnum::MANAGER->value);

        // 4. Create Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'role' => roleEnum::USER,
            ]
        );
        $user->assignRole(roleEnum::USER->value);
    }
}
