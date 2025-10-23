<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Errors\ErrorCodes;
use App\Exceptions\BusinessRuleException;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

/**
 * Service class responsible for handling business logic related to tasks.
 */
class TaskService
{

    /**
     * TaskService constructor.
     *
     * @param TaskRepository $taskRepository
     */
    public function __construct(
        protected TaskRepository $taskRepository
    ) {}

    /**
     * Get all tasks with subtasks for the given user from today up to 1 month.
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllTasks(int $userId): Collection
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonth();
        $tasks = $this->taskRepository->getUserTasksByDateRange($userId, $startDate, $endDate);

        return $this->formatTasksByDateRange($tasks, $startDate, $endDate);
    }

    /**
     * Get today's tasks for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getTodayTasks(int $userId): Collection
    {
        $today = Carbon::today();
        $tasks = $this->taskRepository->getUserTasksByDateRange($userId, $today, $today);

        return $this->formatTasksByDateRange($tasks, $today, $today);
    }

    /**
     * Get planned tasks grouped by date.
     *
     * @param int $userId
     * @param int $days
     * @return Collection
     */
    public function getPlannedTasks(int $userId, int $days): Collection
    {
        $days = min($days, 30);
        $startDate = today();
        $endDate = today()->addDays($days);

        $tasks = $this->taskRepository->getUserTasksByDateRange($userId, $startDate, $endDate);

        return $this->formatTasksByDateRange($tasks, $startDate, $endDate);
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
        return $this->taskRepository->findById($userId, $taskId);
    }

    /**
     * Get company associated with the task
     *
     * @param int $userId
     * @param int $taskId
     * @return Task
     */
    public function getTaskWithCompany(int $userId, int $taskId): Task
    {
        $task = $this->taskRepository->findCompany($userId, $taskId);
        if (!$task) {
            throw new ModelNotFoundException("Task not found or not authorized");
        }

        if (!$task->user->company) {
            throw new BusinessRuleException(
                'No company associated with this task',
                400,
                ErrorCodes::TASK_NO_COMPANY,
                'TASKS'
            );
        }

        return $task;
    }

    /**
     * Get tasks for a specific date for a user.
     *
     * @param int $userId
     * @param string $date Date in YYYY-MM-DD format
     * @return Collection
     */
    public function getTasksByDate(int $userId, string $date): Collection
    {
        $date = Carbon::parse($date)->startOfDay();
        $tasks = $this->taskRepository->tasksByDate($userId, $date);

        return $this->formatTasksByDateRange($tasks, $date, $date);
    }

    /**
     * Get tasks for a specific day offset (0 = today) for a user.
     *
     * @param int $userId
     * @param int $offset
     * @return Collection
     */
    public function getTasksByDayOffset(int $userId, int $offset): Collection
    {
        $startDate = Carbon::today();
        $endDate   = Carbon::today()->addDays($offset);
        $tasks = $this->taskRepository->getUserTasksByDateRange($userId, $startDate, $endDate);

        return $this->formatTasksByDateRange($tasks, $startDate, $endDate);
    }

    /**
     * Format a collection of tasks into an array grouped by date within a given range.
     *
     * @param Collection $tasks
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function formatTasksByDateRange(Collection $tasks, Carbon $startDate, Carbon $endDate): Collection
    {
        $period    = CarbonPeriod::create($startDate, $endDate);

        $grouped = $tasks->groupBy(function ($task) {
            return Carbon::parse($task->scheduled_date)->format('d/m/Y');
        });

        return collect($period)->map(function ($date) use ($grouped) {
            $formattedDate = $date->format('d/m/Y');

            $dayTasks = $grouped->get($formattedDate, collect());

            return [
                'date' => $formattedDate,
                'taskCount' => $dayTasks->count(),
                'tasks' => $dayTasks->map(fn($task) => $task->toArray())->values(),
            ];
        })->values();
    }

    /**
     * Returns users with their processed tasks (adding labels, colors, etc.)
     *
     * @param string|null $userName
     * @param string|null $taskTitle
     * @param string|null $status
     * @param string|null $date
     *
     * @return LengthAwarePaginator
     */
    public function getProcessedUsersWithTasks(
        ?string $userName,
        ?string $taskTitle,
        ?string $status,
        ?string $date
    ): LengthAwarePaginator {
        $users = $this->taskRepository->getFilteredUsersWithTasks($userName, $taskTitle, $status, $date);

        $users->getCollection()->transform(function ($user) {
            $user->tasks->transform(function ($task) {
                $isCompleted = $task->status === TaskStatus::COMPLETED->value;
                $task->is_completed = $isCompleted;
                $task->status_label = TaskStatus::label(TaskStatus::from($task->status));
                $task->is_recurrent = !is_null($task->recurrent_task_id);

                $task->subtasks->transform(function ($subtask) {
                    $isCompleted = $subtask->status === TaskStatus::COMPLETED->value;
                    $subtask->is_completed = $isCompleted;
                    $subtask->status_label = TaskStatus::label(TaskStatus::from($subtask->status));
                    return $subtask;
                });

                return $task;
            });

            $user->tasks_by_date = $user->tasks
                ->groupBy(function ($task) {
                    return $task->scheduled_date
                        ? Carbon::parse($task->scheduled_date)->format('d-m-Y')
                        : 'sin_fecha';
                });

            return $user;
        });

        return $users;
    }
}
