<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Admins and Managers can create tasks in any project.
     * Regular Users can only create tasks in projects they are members of.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        // Admins and Managers can always create tasks
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Regular Users: Must be a member of the target project
        $projectId = $this->input('project_id');

        if (!$projectId) {
            return false; // Will be caught by validation rules
        }

        $project = Project::find($projectId);

        if (!$project) {
            return false; // Will be caught by validation rules
        }

        return $project->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'project_id' => ['required', 'exists:projects,id'],
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $projectId = $this->input('project_id');
                    if ($projectId && $value) {
                        $exists = DB::table('project_user')
                            ->where('project_id', $projectId)
                            ->where('user_id', $value)
                            ->exists();

                        if (!$exists) {
                            $fail('The assigned user must be a member of the project.');
                        }
                    }
                },
            ],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
        ];
    }
}
