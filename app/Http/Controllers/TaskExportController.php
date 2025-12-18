<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\SimpleExcel\SimpleExcelWriter;

class TaskExportController extends Controller
{
    use AuthorizesRequests;

    /**
     * Export tasks to Excel.
     */
    public function __invoke()
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
                AllowedFilter::partial('title'),
                'status',
                'priority',
                'due_date',
                'project_id',
                AllowedFilter::exact('assigned_to_user_id', 'assigned_to'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts(['due_date', 'created_at', 'priority', 'title'])
            ->with(['project', 'assignee']) // Eager load for export
            ->lazy(); // Use lazy() for memory efficient streaming

        $writer = SimpleExcelWriter::streamDownload('tasks.xlsx');

        foreach ($tasks as $task) {
            $writer->addRow([
                'ID' => $task->id,
                'Title' => $task->title,
                'Status' => $task->status->value,
                'Priority' => $task->priority->value,
                'Assigned User Name' => $task->assignee ? $task->assignee->name : 'Unassigned',
                'Project Title' => $task->project ? $task->project->title : 'N/A',
                'Due Date' => $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A',
            ]);
        }

        return $writer->toBrowser();
    }
}
