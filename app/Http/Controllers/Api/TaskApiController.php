<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;

class TaskApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Get all tasks and their subtasks for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allTasksWithSubtasks(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $tasks = Task::with('subtasks')
                ->where('user_id', $request->user()->id)
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            return $this->render($tasks);
        }, 'Error getting tasks with subtasks', $request);
    }

    /**
     * Returns the tasks scheduled for today for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function tasksToday(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = $request->user()->id;
            $tasks = Task::where('user_id', $userId)
                ->whereDate('scheduled_date', now()->toDateString())
                ->with('subtasks')
                ->get();

            if ($tasks->isEmpty()) {
                return $this->render(null, 'This user has no tasks for today', 200);
            }
            return $this->render($tasks);

        }, "Error getting today's tasks", $request);
    }

    /**
     * Returns the tasks planned for the next N days (maximum 30).
     *
     * @param Request $request
     * @param int $days
     * @return JsonResponse
     *
     */
    public function plannedTasks(Request $request, int $days): JsonResponse
    {
        return $this->handleApi(function () use ($days, $request) {
            $days = min($days, 30);

            $tasks = Task::with(['subtasks'])
                ->where('user_id', $request->user()->id)
                ->whereBetween('scheduled_date', [now(), now()->addDays($days)])
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            if ($tasks->isEmpty()) {
                return $this->render(null, 'This user has no tasks scheduled for the following'. $days .' days', 200);
            }
            return $this->render($tasks);
        }, 'Error getting scheduled tasks', $request);
    }

    /**
     * Displays the details of a task for the authenticated user.
     *
     * @param Request $request
     * @param int $task_id
     * @return JsonResponse
     *
     */
    public function show(Request $request, int $task_id): JsonResponse
    {
        return $this->handleApi(function () use ($task_id, $request) {
            $tasks =  Task::with([
                'subtasks' => fn($q) => $q->orderBy('order'),
                'assignedBy:id,name,surname'
            ])
                ->where('user_id', $request->user()->id)
                ->findOrFail($task_id);
            return $this->render($tasks);
        }, 'Error getting task details', $request);
    }

    /**
     * Returns a summary of the status of the user's tasks.
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function statusSummary(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = $request->user()->id;

            return response()->json([
                'completed'    => Task::where('user_id', $userId)->where('status', 'completed')->count(),
                'pending'      => Task::where('user_id', $userId)->where('status', 'pending')->count()
            ]);
        }, 'Error getting task summary', $request);
    }

    /**
     * View companies associated with a specific task via URL param
     *
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     *
     */
    public function getCompaniesByTask(Request $request, int $taskId): JsonResponse
    {
        return $this->handleApi(function () use ($request, $taskId) {
            $userId = $request->user()->id;

            $task = Task::where('id', $taskId)
                ->where('user_id', $userId)
                ->with('user.company.phones')
                ->first();

            if (!$task) {
                return $this->render(null, 'Task not found or not authorized', 404);
            }

            if (!$task->user->company) {
                return $this->render(null, 'No company associated with this task', 200);
            }

            return $this->render($task->user->company);
        }, "Error retrieving companies for the task", $request);
    }
}
