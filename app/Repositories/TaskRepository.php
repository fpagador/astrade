<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

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
            ->firstOrFail();
    }

    /**
     * Get all tasks for a user ordered by scheduled_date and scheduled_time.
     *
     * @param int $userId
     * @return Collection|Task[]
     */
    public function getUserTasks(int $userId): Collection
    {
        return Task::where('user_id', $userId)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->with('subtasks')
            ->get();
    }

    /**
     * Paginate tasks for a user on a specific date with optional filters.
     *
     * @param int $userId
     * @param string $date  ISO date string (Y-m-d)
     * @param array $filters  ['title' => string, 'status' => string]
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserTasksByDate(int $userId, string $date, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with('subtasks')->where('user_id', $userId);

        $query->whereDate('scheduled_date', $date);

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate(15);
    }

    /**
     * Check if the user has any tasks.
     *
     * @param int $userId
     * @return bool
     */
    public function hasAnyTasks(int $userId): bool
    {
        return Task::where('user_id', $userId)->exists();
    }

    /**
     * Find a task with given relations.
     *
     * @param int $taskId
     * @param array $relations
     * @return Task
     */
    public function findWithRelations(int $taskId, array $relations = []): Task
    {
        return Task::with($relations)->findOrFail($taskId);
    }

    /**
     * Create a task record.
     *
     * @param array $attributes
     * @return Task
     */
    public function create(array $attributes): Task
    {
        return Task::create($attributes);
    }

    /**
     * Update a task by model.
     *
     * @param Task $task
     * @param array $attributes
     * @return Task
     */
    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);
        return $task;
    }

    /**
     * Delete a task.
     *
     * @param Task $task
     * @return void
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Get all tasks with relations (used for create view listing existing tasks).
     *
     * @return Collection
     */
    public function getAllWithRelations(): Collection
    {
        return Task::with(['user', 'subtasks'])->latest()->get();
    }

    /**
     * Delete many tasks by IDs (no file cleanup here; service should handle files).
     *
     * @param array $ids
     * @return void
     */
    public function deleteMany(array $ids): void
    {
        Task::whereIn('id', $ids)->delete();
    }

    /**
     * Check if a task exists for the given user, date, and time.
     *
     * @param int $userId
     * @param string $date
     * @param string $time
     * @return bool
     */
    public function existsForUserAtDateTime(int $userId, string $date, string $time): bool
    {
        return Task::where('user_id', $userId)
            ->where('scheduled_date', $date)
            ->where('scheduled_time', $time)
            ->exists();
    }

    /**
     * Get a task with the necessary relations for editing.
     *
     * @param int $taskId
     * @return Task|null
     */
    public function findWithRelationsForEdit(int $taskId): ?Task
    {
        return Task::with(['recurrentTask', 'subtasks'])->find($taskId);
    }

    /**
     * Convert a task's subtasks into an array ready for the view.
     *
     * @param Task $task
     * @return array<int, array<string, mixed>>
     */
    public function getSubtasksArray(Task $task): array
    {
        return $task->subtasks()
            ->orderBy('order')
            ->get()
            ->map(fn($st) => [
                'id' => $st->id,
                'title' => $st->title,
                'description' => $st->description,
                'note' => $st->note,
                'pictogram_path' => $st->pictogram_path,
                'status' => $st->status,
                'notifications_enabled' => $st->notifications_enabled,
                'reminder_minutes' => $st->reminder_minutes,
            ])->toArray();
    }

    /**
     * Create task from array data.
     *
     * @param int $userId
     * @param array $data
     * @return Task
     */
    public function createFromData(int $userId, array $data): Task
    {
        return Task::create([
            'user_id' => $userId,
            'assigned_by' => $data['assigned_by'] ?? auth()->id(),
            'title' => $data['title'],
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'pictogram_path' => $data['pictogram_path'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notifications_enabled' => $data['notifications_enabled'] ?? false,
            'reminder_minutes' => $data['reminder_minutes'] ?? null,
        ]);
    }

    /**
     * Replicate a task with new date and recurrentTaskId.
     *
     * @param Task $task
     * @param int $recurrentTaskId
     * @param string $date
     * @return Task
     */
    public function replicateWithDate(Task $task, int $recurrentTaskId, string $date): Task
    {
        $attributes = $task->replicate()->toArray();

        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);

        $attributes['scheduled_date'] = $date;
        $attributes['recurrent_task_id'] = $recurrentTaskId;
        $attributes['status'] = TaskStatus::PENDING->value;

        $newTask = new Task($attributes);
        $newTask->save();

        return $newTask;
    }

    /**
     * Get all tasks of a recurrent series (past and future)
     *
     * @param int $recurrentTaskId
     * @return Collection
     */
    public function getAllRecurrentTasks(int $recurrentTaskId): Collection
    {
        return Task::with('subtasks')
            ->where('recurrent_task_id', $recurrentTaskId)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Get all future tasks of a recurrent series.
     *
     * @param int $recurrentTaskId
     * @return Collection
     */
    public function getFutureRecurrentTasks(int $recurrentTaskId, string $cutoffDate = null): Collection
    {
        $cutoffDate = $cutoffDate ?? now()->toDateString();

        return Task::where('recurrent_task_id', $recurrentTaskId)
            ->whereDate('scheduled_date', '>=', $cutoffDate)
            ->get();
    }

    /**
     * Delete tasks by recurrentTaskId and dates.
     *
     * @param int $recurrentTaskId
     * @param array $dates
     * @return void
     */
    public function deleteByDates(int $recurrentTaskId, array $dates): void
    {
        if (empty($dates)) return;

        Task::where('recurrent_task_id', $recurrentTaskId)
            ->whereIn('scheduled_date', $dates)
            ->delete();
    }

    /**
     * Count tasks by scheduled date.
     *
     * @param Carbon $date
     * @return int
     */
    public function countByDate(Carbon $date): int
    {
        return Task::whereDate('scheduled_date', $date)->count();
    }

    /**
     * Count tasks by status.
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return Task::where('status', $status)->count();
    }
}
