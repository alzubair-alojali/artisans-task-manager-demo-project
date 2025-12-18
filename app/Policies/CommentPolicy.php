<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any comments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task|\App\Models\Project  $commentable
     * @return bool
     */
    public function viewAny(User $user, $commentable): bool
    {
        if ($commentable instanceof Task) {
            return $user->hasRole(UserRole::ADMIN->value) ||
                $commentable->project->manager_id === $user->id ||
                $commentable->project->members()->where('users.id', $user->id)->exists();
        }

        if ($commentable instanceof Project) {
            return $user->hasRole(UserRole::ADMIN->value) ||
                $commentable->manager_id === $user->id ||
                $commentable->members()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create comments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Task|\App\Models\Project  $commentable
     * @return bool
     */
    public function create(User $user, $commentable): bool
    {
        return $this->viewAny($user, $commentable);
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // 1. Author can delete
        if ($user->id === $comment->user_id) {
            return true;
        }

        // 2. Admin/Manager of the parent project can delete
        $project = null;

        if ($comment->commentable_type === Task::class) {
            $project = $comment->commentable->project;
        } elseif ($comment->commentable_type === Project::class) {
            $project = $comment->commentable;
        }

        if ($project) {
            return $user->id === $project->manager_id || $user->hasRole(UserRole::ADMIN->value);
        }

        return false;
    }
}
