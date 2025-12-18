<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskExportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// Public Routes
// =========================================================================

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Socialite (Google)
Route::prefix('auth/google')->group(function () {
    Route::get('/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/callback', [AuthController::class, 'handleGoogleCallback']);
});

// =========================================================================
// Protected Routes (Sanctum)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Authentication ---
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Dashboard ---
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // --- User Management ---
    // Allow all authenticated users to list users (for dropdowns)
    Route::get('/users', [UserController::class, 'index']);

    // Admin-only User Management (Create, Update, Delete, Show)
    Route::middleware('role:admin')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // --- Projects ---
    Route::apiResource('projects', ProjectController::class);

    // Project Comments
    Route::post('/projects/{project}/comments', [ProjectCommentController::class, 'store']);

    // --- Tasks ---
    // Export (Must be defined before resource to avoid conflict with {task})
    Route::get('/tasks/export', TaskExportController::class);

    // Restore & Force Delete
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);
    Route::delete('/tasks/{task}/force-delete', [TaskController::class, 'forceDelete']);

    // Resource
    Route::apiResource('tasks', TaskController::class);

    // Task Comments
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);

});