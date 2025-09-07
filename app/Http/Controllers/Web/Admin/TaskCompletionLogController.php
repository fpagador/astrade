<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Models\TaskCompletionLog;
use Illuminate\View\View;
use Illuminate\Http\Request;

class TaskCompletionLogController extends WebController
{
    /**
     * Display a listing of the task completion logs.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return $this->tryCatch(function () use ($request) {
            $query = TaskCompletionLog::with(['user', 'task', 'subtask']);

            // --- Filters ---
            $query->when($request->filled('user_name'), function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->user_name . '%');
                });
            });

            $query->when($request->filled('task_title'), function ($query) use ($request) {
                $query->whereHas('task', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->task_title . '%');
                });
            });

            $query->when($request->filled('subtask_title'), function ($query) use ($request) {
                $query->whereHas('subtask', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->subtask_title . '%');
                });
            });

            // --- Ordering ---
            $sort = $request->get('sort', 'name'); // default usuario
            $direction = $request->get('direction', 'asc');

            $sortableColumns = ['completed_at'];
            $sortableRelations = [
                'name'    => ['table' => 'users',    'local_key' => 'user_id',    'foreign_key' => 'id', 'column' => 'name'],
                'task'    => ['table' => 'tasks',    'local_key' => 'task_id',    'foreign_key' => 'id', 'column' => 'title'],
                'subtask' => ['table' => 'subtasks', 'local_key' => 'subtask_id', 'foreign_key' => 'id', 'column' => 'title'],
            ];

            if (in_array($sort, $sortableColumns)) {
                $query->orderBy("task_completion_logs.$sort", $direction);
            } elseif (array_key_exists($sort, $sortableRelations)) {
                $relation = $sortableRelations[$sort];

                $query->leftJoin(
                    $relation['table'],
                    "{$relation['table']}.{$relation['foreign_key']}",
                    '=',
                    "task_completion_logs.{$relation['local_key']}"
                )
                    ->orderBy("{$relation['table']}.{$relation['column']}", $direction)
                    ->select('task_completion_logs.*');
            } else {
                // fallback if there is no valid sort
                $query->latest('task_completion_logs.completed_at');
            }

            $logs = $query->paginate(15)->appends($request->query());

            return view('web.admin.task_completion_logs.index', compact('logs'));
        });
    }

}
