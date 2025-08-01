<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TaskCompletionLog;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;

class TaskCompletionLogApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Retrieve the history of completed tasks and subtasks for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function taskCompletions(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $logs = TaskCompletionLog::with(['task', 'subtask'])
                ->where('user_id', $request->user()->id)
                ->orderBy('completed_at', 'desc')
                ->get();

            if ($logs->isEmpty()) {
                return $this->render(null, 'No task completion logs found for this user', 404);
            }
            return $this->render($logs, 'Task completion history retrieved successfully');
        }, 'Failed to fetch task completion history', $request);
    }
}
