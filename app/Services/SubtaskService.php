<?php

namespace App\Services;

use App\Repositories\SubtaskRepository;
use App\Models\Subtask;
use App\Models\TaskCompletionLog;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Collection;

/**
 * Service class for Subtask entity.
 * Implements business rules and logic for subtasks.
 */
class SubtaskService
{
    protected SubtaskRepository $repository;

    public function __construct(SubtaskRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all subtasks of a task.
     *
     * @param int $taskId
     * @return Collection
     */
    public function getSubtasks(int $taskId)
    {
        return $this->repository->allByTask($taskId);
    }

    /**
     * Get all subtasks of a task with images loaded.
     *
     * @param int $taskId
     * @return Collection
     */
    public function getSubtasksByTask(int $taskId)
    {
        return $this->repository->allByTask($taskId);
    }

    /**
     * Update the status of a subtask by ID.
     */
    public function updateStatusById(int $subtaskId, string $status, int $userId): Subtask
    {
        $subtask = $this->repository->findById($subtaskId);
        return $this->updateStatus($subtask, $status, $userId);
    }

    /**
     * Update status of a subtask.
     *
     * Business rules:
     * - Only the owner of the parent task can update a subtask.
     * - If all subtasks are completed → parent task becomes completed.
     * - If any subtask is pending → parent task becomes pending.
     *
     * @param Subtask $subtask
     * @param string $status
     * @param int $userId
     * @return Subtask
     * @throws BusinessRuleException
     */
    public function updateStatus(Subtask $subtask, string $status, int $userId): Subtask
    {
        if (!in_array($status, ['completed', 'pending'])) {
            throw new BusinessRuleException('Invalid status. Allowed: completed, pending', 422);
        }

        if (!$subtask->task || $subtask->task->user_id !== $userId) {
            throw new BusinessRuleException('You do not have permission to modify this subtask.', 403);
        }

        DB::transaction(function () use ($subtask, $status, $userId) {
            // Update the subtask status
            $subtask->status = $status;
            $subtask->save();

            // Log the subtask completion/pending
            TaskCompletionLog::create([
                'user_id' => $userId,
                'task_id' => $subtask->task_id,
                'subtask_id' => $subtask->id,
                'completed_at' => now(),
            ]);

            // Update parent task status
            $task = $subtask->task;
            if ($status === 'completed') {
                $allCompleted = $task->subtasks()
                    ->where('status', '!=', 'completed')
                    ->doesntExist();
                if ($allCompleted) {
                    $task->status = 'completed';
                    $task->save();
                }
            } elseif ($status === 'pending' && $task->status === 'completed') {
                $task->status = 'pending';
                $task->save();
            }
        });

        return $subtask;
    }
}
