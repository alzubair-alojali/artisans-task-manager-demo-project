<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
     * @bodyParam title string required The title of the project. Example: New Website
     * @bodyParam description string optional The description of the project. Example: A new website for the client.
     * @bodyParam deadline date required The deadline of the project. Must be a date after today. Example: 2025-12-31
     * @bodyParam status string required The status of the project. Must be one of: open, completed, archived. Example: open
     * @bodyParam manager_id integer optional The ID of the manager. Must exist in users table. Example: 1
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['required', 'date', 'after:today'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'manager_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
