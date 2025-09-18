<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Models\TaskCompletionLog;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\TaskCompletionLogService;

class TaskCompletionLogController extends WebController
{
    /**
     * Constructor
     *
     * @param TaskCompletionLogService $taskCompletionLogService
     */
    public function __construct(
        protected TaskCompletionLogService $taskCompletionLogService,
    ) {}

    /**
     * Display a paginated list of task completion logs with optional filters.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return $this->tryCatch(function () use ($request) {
            $filters = $request->only(['user_name', 'task_title', 'subtask_title']);
            $sort = $request->get('sort', 'completed_at');
            $direction = $request->get('direction', 'asc');

            $logs = $this->taskCompletionLogService->getPaginatedLogs($filters, $sort, $direction);

            return view('web.admin.task_completion_logs.index', compact('logs'));
        });
    }

}
