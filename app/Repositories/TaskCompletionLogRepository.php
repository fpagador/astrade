<?php

namespace App\Repositories;

use App\Models\TaskCompletionLog;
use Illuminate\Support\Collection;

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
}
