<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
