<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Tasks
 *
 * APIs for managing tasks.
 */
class TaskController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all tasks.
     *
     * Retrieve a list of tasks with filtering and sorting.
     * Regular users only see tasks for projects they belong to.
     *
     * @summary List Tasks
     * @response \App\Http\Resources\TaskResource[]
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $user = Auth::user();
        $query = Task::query();

        if ($user->role === UserRole::USER) {
            $query->whereHas('project.members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $tasks = QueryBuilder::for($query)
            ->allowedFilters([
                'project_id',
                'status',
                'priority',
                'assigned_to',
                AllowedFilter::trashed(),
            ])
            ->allowedSorts(['due_date', 'priority'])
            ->with(['project', 'assignee', 'creator'])
            ->paginate();

        return TaskResource::collection($tasks);
    }

    /**
     * Create a new task.
     *
     * Create a new task record.
     *
     * @summary Create Task
     * @response 201 \App\Http\Resources\TaskResource
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $task = Task::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        $task->load(['project', 'assignee', 'creator']);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a task.
     *
     * Retrieve details of a specific task.
     *
     * @summary Show Task
     * @response \App\Http\Resources\TaskResource
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee', 'creator']);

        return new TaskResource($task);
    }

    /**
     * Update a task.
     *
     * Update task details.
     *
     * @summary Update Task
     * @response \App\Http\Resources\TaskResource
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        $task->load(['project', 'assignee', 'creator']);

        return new TaskResource($task);
    }

    /**
     * Delete a task.
     *
     * Remove a task from the system.
     *
     * @summary Delete Task
     * @response 204 {}
     */
    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }

    /**
     * Restore a soft-deleted task.
     *
     * @summary Restore Task
     * @response \App\Http\Resources\TaskResource
     */
    public function restore(int $id): TaskResource
    {
        $task = Task::withTrashed()->findOrFail($id);

        $this->authorize('restore', $task);

        $task->restore();

        $task->load(['project', 'assignee', 'creator']);

        return new TaskResource($task);
    }

    /**
     * Permanently delete a task.
     *
     * @summary Force Delete Task
     * @response 204 {}
     */
    public function forceDelete(int $id): Response
    {
        $task = Task::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $task);

        $task->forceDelete();

        return response()->noContent();
    }
}
