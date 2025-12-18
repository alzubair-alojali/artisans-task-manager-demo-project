<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $cacheKey = 'dashboard_stats_' . $user->id;

        $stats = Cache::remember($cacheKey, 300, function () use ($user) {
            $isAdmin = $user->hasRole(UserRole::ADMIN->value);

            // Base Queries
            $taskQuery = Task::query();
            $projectQuery = Project::query();

            if (!$isAdmin) {
                $taskQuery->where('assigned_to', $user->id);
                $projectQuery->whereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }

            // Execute Queries
            $totalTasks = $taskQuery->count();
            $totalProjects = $projectQuery->count();

            $tasksByStatus = $taskQuery->clone()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $tasksByPriority = $taskQuery->clone()
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority');

            // Calculate Completion Rate
            // Assuming 'done' is the status for completed tasks.
            // We need to check the TaskStatus enum values.
            // Based on previous context, it's likely 'done'.
            // I'll fetch the count of 'done' tasks specifically or use the grouped data.

            // Using the enum value for safety if possible, otherwise string 'done'
            $doneCount = $tasksByStatus->get(TaskStatus::DONE->value) ?? 0;

            $completionRate = $totalTasks > 0
                ? round(($doneCount / $totalTasks) * 100, 2)
                : 0;

            return [
                'total_tasks' => $totalTasks,
                'total_projects' => $totalProjects,
                'tasks_by_status' => $tasksByStatus,
                'tasks_by_priority' => $tasksByPriority,
                'completion_rate' => $completionRate,
            ];
        });

        return response()->json($stats);
    }
}
