<?php

namespace App\Services;

use App\Repositories\TaskRepository;
use Illuminate\Support\Collection;
use App\Models\Task;

/**
 * Service class responsible for handling business logic related to tasks.
 */
class TaskService
{
    protected TaskRepository $repository;

    /**
     * TaskService constructor.
     *
     * @param TaskRepository $repository
     */
    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all tasks with subtasks for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllTasks(int $userId): Collection
    {
        return $this->repository->allByUser($userId);
    }

    /**
     * Get today's tasks for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getTodayTasks(int $userId): Collection
    {
        return $this->repository->todayByUser($userId);
    }

    /**
     * Get planned tasks for the next N days (max 30) for a given user.
     *
     * @param int $userId
     * @param int $days
     * @return Collection
     */
    public function getPlannedTasks(int $userId, int $days): Collection
    {
        return $this->repository->plannedByUser($userId, $days);
    }

    /**
     * Get details of a specific task including subtasks for a given user.
     *
     * @param int $userId
     * @param int $taskId
     * @return Task
     */
    public function getTaskDetails(int $userId, int $taskId): Task
    {
        return $this->repository->findById($userId, $taskId);
    }

    public function getTaskWithCompany(int $userId, int $taskId): Task
    {
        return $this->repository->findCompany($userId, $taskId);
    }
}
