<?php

namespace App\Repositories;

use App\Models\Subtask;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository class for Subtask entity.
 * Handles all database operations related to subtasks.
 */
class SubtaskRepository
{
    /**
     * Get all subtasks of a task sorted by order.
     *
     * @param int $taskId
     * @return Collection
     */
    public function allByTask(int $taskId): Collection
    {
        return Subtask::where('task_id', $taskId)->get();
    }

    /**
     * Find a subtask by ID with its parent task and subtasks loaded.
     *
     * @param int $subtaskId
     * @return Subtask
     * @throws ModelNotFoundException
     */
    public function findById(int $subtaskId): Subtask
    {
        $subtask = Subtask::with('task.subtasks')->find($subtaskId);
        if (!$subtask) {
            throw new ModelNotFoundException("Subtask not found");
        }
        return $subtask;
    }

    /**
     * Save a subtask.
     *
     * @param Subtask $subtask
     * @return Subtask
     */
    public function save(Subtask $subtask): Subtask
    {
        $subtask->save();
        return $subtask;
    }
}
