<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class TaskCommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * List comments for a task.
     */
    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Comment::class, $task]);

        $comments = QueryBuilder::for($task->comments()->getQuery())
            ->allowedSorts(['created_at'])
            ->allowedIncludes(['user'])
            ->paginate(15);

        return CommentResource::collection($comments);
    }

    /**
     * Add a comment to a task.
     */
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('create', [Comment::class, $task]);

        $comment = $task->comments()->create([
            'body' => $request->validated('body'),
            'user_id' => Auth::id(),
        ]);

        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }
}
