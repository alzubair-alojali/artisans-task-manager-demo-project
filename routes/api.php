<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')
        ->apiResource('users', \App\Http\Controllers\UserController::class);

    Route::post('projects/{project}/invite', [ProjectController::class, 'invite']);
    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);
    Route::apiResource('projects', ProjectController::class);

    Route::post('tasks/{id}/restore', [TaskController::class, 'restore']);
    Route::delete('tasks/{id}/force-delete', [TaskController::class, 'forceDelete']);
    Route::apiResource('tasks', TaskController::class);

    // Comments
    Route::apiResource('tasks.comments', \App\Http\Controllers\TaskCommentController::class)->only(['index', 'store']);
    Route::apiResource('projects.comments', \App\Http\Controllers\ProjectCommentController::class)->only(['index', 'store']);
    Route::delete('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy']);
});