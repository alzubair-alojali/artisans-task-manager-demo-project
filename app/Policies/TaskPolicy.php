<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
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
        // Admin can view all
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Manager can view tasks in projects they manage
        if ($user->role === UserRole::MANAGER) {
            return $task->project->manager_id === $user->id
                || $this->isProjectMember($user, $task->project);
        }

        // Regular users can view if they are members of the project
        return $this->isProjectMember($user, $task->project);
    }

    /**
     * Determine whether the user can create models.
     *
     * For regular users, project membership is validated in StoreTaskRequest.
     */
    public function create(User $user, ?Project $project = null): bool
    {
        // Admin and Manager can always create
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Regular User: Allow only if project is provided and they are a member
        if ($project !== null) {
            return $this->isProjectMember($user, $project);
        }

        // If no project context, allow - the StoreTaskRequest will validate membership
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Admin can update all
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Manager: Can only update tasks in projects they manage
        if ($user->role === UserRole::MANAGER) {
            return $task->project->manager_id === $user->id;
        }

        // Regular User: Can update if:
        // 1. Task is assigned to them, OR
        // 2. Task is unassigned (pick-up scenario)
        // AND they must be a project member
        $isProjectMember = $this->isProjectMember($user, $task->project);

        if (!$isProjectMember) {
            return false;
        }

        // Allow if assigned to them OR task is unassigned (for self-assignment)
        return $task->assigned_to === $user->id || $task->assigned_to === null;
    }

    /**
     * Determine whether the user can delete the model (soft delete).
     */
    public function delete(User $user, Task $task): bool
    {
        // Admin can delete all
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Manager: Can only delete tasks in projects they manage
        if ($user->role === UserRole::MANAGER) {
            return $task->project->manager_id === $user->id;
        }

        // Regular User: Cannot delete
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        // Admin can restore all
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Manager: Can only restore tasks in projects they manage
        if ($user->role === UserRole::MANAGER) {
            return $task->project->manager_id === $user->id;
        }

        // Regular User: Cannot restore
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        // Admin can force delete all
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Manager: Can only force delete tasks in projects they manage
        if ($user->role === UserRole::MANAGER) {
            return $task->project->manager_id === $user->id;
        }

        // Regular User: Cannot force delete
        return false;
    }

    /**
     * Helper method to check if user is a member of the project.
     */
    private function isProjectMember(User $user, Project $project): bool
    {
        return $project->members()->where('users.id', $user->id)->exists();
    }
}
