<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository class responsible for managing task data persistence.
 */
class TaskRepository
{
    /**
     * Get all tasks with subtasks for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function allByUser(int $userId): Collection
    {
        return Task::with('subtasks')
            ->where('user_id', $userId)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Get today's tasks with subtasks for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function todayByUser(int $userId): Collection
    {
        return Task::with('subtasks')
            ->where('user_id', $userId)
            ->whereDate('scheduled_date', now()->toDateString())
            ->get();
    }

    /**
     * Get planned tasks for the next N days (max 30) for a given user.
     *
     * @param int $userId
     * @param int $days
     * @return Collection
     */
    public function plannedByUser(int $userId, int $days): Collection
    {
        $days = min($days, 30);

        return Task::with('subtasks')
            ->where('user_id', $userId)
            ->whereBetween('scheduled_date', [now(), now()->addDays($days)])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Find a specific task with its subtasks for a given user.
     *
     * @param int $userId
     * @param int $taskId
     * @return Task
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $userId, int $taskId): Task
    {
        $task = Task::with(['subtasks' => fn($q) => $q->orderBy('order')])
            ->where('user_id', $userId)
            ->find($taskId);

        if (!$task) {
            throw new ModelNotFoundException("Task not found or unauthorized");
        }

        return $task;
    }

    /**
     * Find a specific task with its subtasks for a given user.
     *
     * @param int $userId
     * @param int $taskId
     * @return Task
     *
     * @throws ModelNotFoundException
     */
    public function findCompany(int $userId, int $taskId): Task
    {
        return Task::where('id', $taskId)
            ->where('user_id', $userId)
            ->with('user.company.phones')
            ->first();
    }
}
