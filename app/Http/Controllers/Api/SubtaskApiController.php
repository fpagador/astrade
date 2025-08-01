<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\TaskCompletionLog;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;

class SubtaskApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Get subtasks of a task (sorted)
     *
     * @param int $task_id
     * @return JsonResponse
     */
    public function index(int $task_id): JsonResponse
    {
        return $this->handleApi(function () use ($task_id) {
            $subtasks = Subtask::where('task_id', $task_id)
                ->orderBy('order')
                ->get();
            return $this->render($subtasks);
        }, 'Error getting subtasks');
    }

    /**
     * Get subtasks with images.
     *
     * @param int $task_id
     * @return JsonResponse
     */
    public function byTask(int $task_id): JsonResponse
    {
        return $this->handleApi(function () use ($task_id) {
            $subtasks = Subtask::with('images')
                ->where('task_id', $task_id)
                ->orderBy('order')
                ->get();
            return $this->render($subtasks);
        }, 'Error getting subtasks with images');
    }

    /**
     * Mark a subtask as complete (sets the status to 'completed').
     *
     * @param int $subtask_id
     * @return JsonResponse
     */
    public function complete(int $subtask_id): JsonResponse
    {
        return $this->handleApi(function () use ($subtask_id) {
            $user = auth()->user();
            $subtask = Subtask::with('task')->findOrFail($subtask_id);

            if (!$subtask->task || $subtask->task->user_id !== $user->id) {
                return $this->render($subtask, 'You do not have permission to modify this subtask.',403);
            }

            // Check if already completed
            if ($subtask->status === 'completed') {
                return $this->render($subtask, 'This subtask is already completed.',400);
            }

            // Update status
            $subtask->status = 'completed';
            $subtask->save();

            return $this->render($subtask, 'Subtask marked as completed');
        }, 'Error marking subtask as completed');
    }

    /**
     * Mark a subtask as completed and record the log.
     *
     * @param Request $request
     * @param int $subtask_id
     * @return JsonResponse
     */
    public function markComplete(Request $request, int $subtask_id): JsonResponse
    {
        return $this->handleApi(function () use ($subtask_id, $request) {
            $subtask = Subtask::findOrFail($subtask_id);

            if (!in_array($subtask->status, ['pending', 'in_progress'])) {
                return response()->json(['message' => 'Subtask already completed or invalid'], 400);
            }

            DB::transaction(function () use ($subtask, $request) {
                $subtask->update(['status' => 'completed']);

                TaskCompletionLog::create([
                    'user_id'      => $request->user()->id,
                    'task_id'      => $subtask->task_id,
                    'subtask_id'   => $subtask->id,
                    'completed_at' => now(),
                ]);
            });

            return $this->render($subtask, 'Subtask completed successfully');
        }, 'Error completing subtask', $request);
    }
}
