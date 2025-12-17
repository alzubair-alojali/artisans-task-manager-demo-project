<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectCommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * List comments for a project.
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Comment::class, $project]);

        $comments = QueryBuilder::for($project->comments()->getQuery())
            ->allowedSorts(['created_at'])
            ->with(['user'])
            ->paginate();

        return CommentResource::collection($comments);
    }

    /**
     * Add a comment to a project.
     */
    public function store(StoreCommentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Comment::class, $project]);

        $comment = $project->comments()->create([
            'body' => $request->validated('body'),
            'user_id' => Auth::id(),
        ]);

        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }
}
