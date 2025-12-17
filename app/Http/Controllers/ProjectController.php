<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
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
        $projects = QueryBuilder::for(Project::class)
            ->allowedFilters(['status', 'title'])
            ->allowedSorts(['deadline', 'created_at'])
            ->allowedIncludes(['manager'])
            ->withCount(['members', 'tasks'])
            ->get();

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
}
