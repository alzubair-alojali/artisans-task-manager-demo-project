<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): Response
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
