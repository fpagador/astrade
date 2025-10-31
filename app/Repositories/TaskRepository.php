<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection as CollectionDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Repository class responsible for managing task data persistence.
 */
class TaskRepository
{

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
     * It retrieves all of the user's tasks and subtasks (including recurring ones) from two months prior.
     *
     * @param int $userId
     * @return Collection
     */
    public function getForUserWithRelations(int $userId): Collection
    {
        $twoMonthsAgo = now()->subMonths(2)->toDateString();

        $normalTasks = Task::with(['subtasks'])
            ->where('user_id', $userId)
            ->whereNull('recurrent_task_id')
            ->whereDate('scheduled_date', '>=', $twoMonthsAgo)
            ->orderByDesc('scheduled_date')
            ->get();

        $recurrentTasks = Task::with(['subtasks'])
            ->where('user_id', $userId)
            ->whereNotNull('recurrent_task_id')
            ->whereDate('scheduled_date', '>=', $twoMonthsAgo)
            ->orderByDesc('scheduled_date')
            ->get()
            ->groupBy('recurrent_task_id')
            ->map(fn($tasks) => $tasks->first())
            ->values();

        return $normalTasks
            ->merge($recurrentTasks)
            ->sortByDesc('scheduled_date')
            ->values();
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
                'external_id' => $st->external_id,
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
        return new Task([
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
     * Get all future tasks of a recurrent series.
     *
     * @param int $recurrentTaskId
     * @return CollectionDatabase
     */
    public function getFutureRecurrentTasks(int $recurrentTaskId, string $cutoffDate = null): CollectionDatabase
    {
        $cutoffDate = $cutoffDate ?? now()->toDateString();

        return Task::where('recurrent_task_id', $recurrentTaskId)
            ->whereDate('scheduled_date', '>=', $cutoffDate)
            ->with('subtasks')
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

    /**
     * Get task count grouped by day for a date range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array<string,int>
     */
    public function getTasksCountGroupedByDay(Carbon $start, Carbon $end): array
    {
        return Task::selectRaw('DATE(scheduled_date) as day, COUNT(*) as total')
            ->whereBetween('scheduled_date', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();
    }

    /**
     * Get tasks for a specific date.
     *
     * @param Carbon $date
     * @return CollectionDatabase
     */
    public function getTasksByDate(Carbon $date): CollectionDatabase
    {
        return Task::with('user:id,name,surname')
            ->whereDate('scheduled_date', $date)
            ->get(['id', 'title', 'status', 'user_id']);
    }

    /**
     * Get tasks assigned to a specific user.
     *
     * @param int $userId
     * @return CollectionDatabase
     */
    public function getTasksByUser(int $userId): CollectionDatabase
    {
        return Task::where('user_id', $userId)
            ->where('status', 'pending')
            ->get(['id', 'title']);
    }

    /**
     * Delete task with the pictogram
     *
     * @param Collection|Task $task
     * @return void
     */
    public function deleteWithFiles(Collection|Task $task): void
    {
        $tasks = $task instanceof Task ? collect([$task]) : $task;
        // Delete pictogram
        foreach ($tasks as $t) {
            // Delete pictogram if exists
            if ($t->pictogram_path && Storage::disk('public')->exists($t->pictogram_path)) {
                Storage::disk('public')->delete($t->pictogram_path);
            }

            // Delete the task itself
            $t->delete();
        }
    }

    /**
     * Get tasks for a specific date for a user.
     *
     * @param int $userId
     * @param string $date
     * @return Collection
     */
    public function tasksByDate(int $userId, string $date): Collection
    {
        return Task::with('subtasks')
            ->where('user_id', $userId)
            ->whereDate('scheduled_date', $date)
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Get tasks for a specific day offset for a user.
     *
     * @param int $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getUserTasksByDateRange(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Task::with('subtasks')
            ->where('user_id', $userId)
            ->whereBetween('scheduled_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * Get tasks for a specific recurrentID
     *
     * @param int $recurrentId
     * @return Collection
     */
    public function getTasksByRecurrentId(int $recurrentId): Collection
    {
        return Task::query()
            ->where('recurrent_task_id', $recurrentId)
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Get all tasks assigned to a user on specific dates.
     *
     * @param int $userId
     * @param array $dates
     * @return Collection
     */
    public function getTasksByUserAndDates(int $userId, array $dates): Collection
    {
        return Task::where('user_id', $userId)
            ->whereIn('scheduled_date', $dates)
            ->get();
    }

    /**
     * Delete tasks for a user on specific dates.
     *
     * @param int $userId
     * @param array $dates
     * @return void
     */
    public function deleteTasksByUserAndDates(int $userId, array $dates): void
    {
        Task::where('user_id', $userId)
            ->whereIn('scheduled_date', $dates)
            ->delete();
    }
}
