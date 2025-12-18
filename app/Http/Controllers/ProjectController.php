<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Requests\Projects\InviteMemberRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Projects
 *
 * APIs for managing projects.
 */
class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all projects.
     *
     * Retrieve a list of projects with filtering and sorting.
     *
     * @summary List Projects
     * @response \App\Http\Resources\ProjectResource[]
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $user = Auth::user();
        $query = Project::query();

        if ($user->role === UserRole::USER) {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $projects = QueryBuilder::for($query)
            ->allowedFilters(['status', 'title'])
            ->allowedSorts(['deadline', 'created_at'])
            ->allowedIncludes(['manager'])
            ->withCount(['members', 'tasks'])
            ->paginate();

        return ProjectResource::collection($projects);
    }

    /**
     * Create a new project.
     *
     * Create a new project record.
     *
     * @summary Create Project
     * @response 201 \App\Http\Resources\ProjectResource
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();

        if (!isset($data['manager_id'])) {
            $data['manager_id'] = Auth::id();
        }

        $project = Project::create($data);
        $project->load('manager');

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a project.
     *
     * Retrieve details of a specific project.
     *
     * @summary Show Project
     * @response \App\Http\Resources\ProjectResource
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $project->load(['manager', 'members', 'tasks']);
        $project->loadCount(['members', 'tasks']);

        return new ProjectResource($project);
    }

    /**
     * Update a project.
     *
     * Update project details.
     *
     * @summary Update Project
     * @response \App\Http\Resources\ProjectResource
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project);
    }

    /**
     * Delete a project.
     *
     * Remove a project from the system.
     *
     * @summary Delete Project
     * @response 204 {}
     */
    public function destroy(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }

    /**
     * Invite a user to the project.
     *
     * Add a user to the project members.
     *
     * @summary Invite Member
     * @response 200 {"message": "User invited to project successfully."}
     */
    public function invite(InviteMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->members()->syncWithoutDetaching([$request->validated('user_id')]);

        return response()->json(['message' => 'User invited to project successfully.']);
    }

    /**
     * Remove a user from the project.
     *
     * Remove a user from the project members.
     *
     * @summary Remove Member
     * @response 204 {}
     */
    public function removeMember(Project $project, User $user): Response
    {
        $this->authorize('update', $project);

        $project->members()->detach($user->id);

        return response()->noContent();
    }
}
