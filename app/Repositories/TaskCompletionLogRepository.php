<?php

namespace App\Repositories;

use App\Models\TaskCompletionLog;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Repository class for handling database interactions related to task completion logs.
 */
class TaskCompletionLogRepository
{
    /**
     * Get all completion logs for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getByUserId(int $userId): Collection
    {
        return TaskCompletionLog::with(['task', 'subtask'])
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /**
     * Build a base query for task completion logs with optional filters.
     *
     * @param array $filters
     * @return Builder
     */
    public function query(array $filters = []): Builder
    {
        $query = TaskCompletionLog::with(['user', 'task', 'subtask']);

        // --- Filters ---
        if (!empty($filters['user_name'])) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $filters['user_name'] . '%'));
        }

        if (!empty($filters['task_title'])) {
            $query->whereHas('task', fn($q) => $q->where('title', 'like', '%' . $filters['task_title'] . '%'));
        }

        if (!empty($filters['subtask_title'])) {
            $query->whereHas('subtask', fn($q) => $q->where('title', 'like', '%' . $filters['subtask_title'] . '%'));
        }

        return $query;
    }

    /**
     * Get paginated task completion logs with optional filters and sorting.
     *
     * @param array $filters
     * @param string $sort
     * @param string $direction
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], string $sort = 'completed_at', string $direction = 'asc', int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query($filters);

        // Columns that belong to the main table
        $sortableColumns = ['completed_at'];

        // Columns belonging to related tables
        $sortableRelations = [
            'name'    => ['table' => 'users',    'local_key' => 'user_id',    'foreign_key' => 'id', 'column' => 'name'],
            'task'    => ['table' => 'tasks',    'local_key' => 'task_id',    'foreign_key' => 'id', 'column' => 'title'],
            'subtask' => ['table' => 'subtasks', 'local_key' => 'subtask_id', 'foreign_key' => 'id', 'column' => 'title'],
        ];

        if (in_array($sort, $sortableColumns)) {
            $query->orderBy("task_completion_logs.$sort", $direction);
        } elseif (array_key_exists($sort, $sortableRelations)) {
            $relation = $sortableRelations[$sort];
            $query->leftJoin(
                $relation['table'],
                "{$relation['table']}.{$relation['foreign_key']}",
                '=',
                "task_completion_logs.{$relation['local_key']}"
            )
                ->orderBy("{$relation['table']}.{$relation['column']}", $direction)
                ->select('task_completion_logs.*');
        } else {
            // fallback
            $query->latest('task_completion_logs.completed_at');
        }

        return $query->paginate($perPage)->appends($filters);
    }
}
