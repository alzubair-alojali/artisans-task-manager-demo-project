<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['sometimes', 'date'],
            'project_id' => ['sometimes', 'exists:projects,id'],
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $projectId = $this->input('project_id');

                    if (!$projectId) {
                        $task = $this->route('task');
                        $projectId = $task?->project_id;
                    }

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
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
        ];
    }
}
