<?php

namespace App\Repositories;

use App\Models\Subtask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Task;

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
     * @return Subtask|Model
     * @throws ModelNotFoundException
     */
    public function findById(int $subtaskId): Subtask|Model
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

    /**
     * Create multiple subtasks from array.
     *
     * @param Task $task
     * @param array $subtasks
     * @return void
     */
    public function createManyFromArray(Task $task, array $subtasks): void
    {
        foreach ($subtasks as $subtask) {
            $task->subtasks()->create($subtask);
        }
    }

    /**
     * Replicate many subtasks from one task to another.
     *
     * @param iterable $subtasks
     * @param Task $newTask
     * @return void
     */
    public function replicateMany(iterable $subtasks, Task $newTask): void
    {
        foreach ($subtasks as $subtask) {
            $attributes = $subtask->replicate()->toArray();
            unset($attributes['id'], $attributes['task_id'], $attributes['created_at'], $attributes['updated_at']);
            $attributes['task_id'] = $newTask->id;
            Subtask::create($attributes);
        }
    }
}
