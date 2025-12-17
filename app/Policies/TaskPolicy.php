<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtering is handled in the controller
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // Admin and Manager can view all
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Regular users can view if they are members of the project
        return $task->project()->whereHas('members', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Regular User: Allow ONLY if assigned_to is them
        return $user->id === $task->assigned_to;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }
}
