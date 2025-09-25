<?php

namespace App\Repositories;

use App\Models\Subtask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
     * @param  array $subtask
     * @return void
     */
    public function createManyFromArray(Task $task, array $subtask): void
    {
        $task->subtasks()->create($subtask);
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

    /**
     * Count subtasks that are pending and delayed (task scheduled date < today).
     *
     * @param Carbon $today
     * @return int
     */
    public function countDelayed(Carbon $today): int
    {
        return Subtask::where('status', 'pending')
            ->whereHas('task', fn($q) => $q->whereDate('scheduled_date', '<', $today))
            ->count();
    }

    /**
     * Update or create a subtask for the given task.
     *
     * @param Task   $task
     * @param string $externalId
     * @param array  $values
     * @return Subtask
     */
    public function updateOrCreate(Task $task, string $externalId, array $values): Subtask
    {
        return $task->subtasks()->updateOrCreate(
            ['external_id' => $externalId],
            $values
        );
    }

    /**
     * Delete all subtasks not in the given list of external IDs.
     */
    public function deleteAllExcept(Task $task, array $externalIds): void
    {
        $task->subtasks()
            ->whereNotIn('external_id', $externalIds)
            ->get()
            ->each(function (Subtask $sub) {
                if ($sub->pictogram_path && Storage::disk('public')->exists($sub->pictogram_path)) {
                    Storage::disk('public')->delete($sub->pictogram_path);
                }
                $sub->delete();
            });
    }

    public function deleteWithFiles(Collection|Subtask $subtasks): int
    {
        $subtasks = $subtasks instanceof Subtask ? collect([$subtasks]) : $subtasks;
        $count = 0;

        foreach ($subtasks as $subtask) {
            // Delete pictogram
            if ($subtask->pictogram_path && Storage::disk('public')->exists($subtask->pictogram_path)) {
                Storage::disk('public')->delete($subtask->pictogram_path);
            }

            $subtask->delete();
            $count++;
        }

        return $count;
    }

}
