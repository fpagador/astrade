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
        return $this->tryCatch(function () use ( $request) {
            $logs = TaskCompletionLog::with(['user', 'task', 'subtask'])
                ->when($request->filled('user_name'), function ($query) use ($request) {
                    $query->whereHas('user', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->user_name . '%');
                    });
                })
                ->when($request->filled('task_title'), function ($query) use ($request) {
                    $query->whereHas('task', function ($q) use ($request) {
                        $q->where('title', 'like', '%' . $request->task_title . '%');
                    });
                })
                ->when($request->filled('subtask_title'), function ($query) use ($request) {
                    $query->whereHas('subtask', function ($q) use ($request) {
                        $q->where('title', 'like', '%' . $request->subtask_title . '%');
                    });
                })
                ->latest()
                ->paginate(15);

            return view('web.admin.task_completion_logs.index', compact('logs'));
        });
    }

}
