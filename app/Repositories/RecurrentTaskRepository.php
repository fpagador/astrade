<?php

namespace App\Repositories;

use App\Models\RecurrentTask;
use App\Models\Task;
use Illuminate\Support\Carbon;

class RecurrentTaskRepository
{
    /**
     * Create recurrent task from request data.
     *
     * @param int $userId
     * @param array $data
     * @return RecurrentTask
     */
    public function createFromData(int $userId, array $data): RecurrentTask
    {
        return RecurrentTask::create([
            'user_id' => $userId,
            'days_of_week' => json_encode($data['days_of_week']),
            'start_date' => $data['recurrent_start_date'] ?? $data['scheduled_date'] ?? null,
            'end_date' => $data['recurrent_end_date'] ?? null,
        ]);
    }

    /**
     * Update or create recurrent task from data.
     *
     * @param Task $task
     * @param array $data
     * @return RecurrentTask
     */
    public function updateOrCreateFromData(Task $task, array $data): RecurrentTask
    {
        $days = $data['days_of_week'] ?? [];
        $startDate = $data['recurrent_start_date'] ?? $task->recurrentTask?->start_date;
        $endDate   = $data['recurrent_end_date'] ?? $task->recurrentTask?->end_date;

        if ($task->recurrentTask) {
            $task->recurrentTask->update([
                'days_of_week' => json_encode($days),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            return $task->recurrentTask;
        }

        $recurrent = RecurrentTask::create([
            'user_id' => $task->user_id,
            'days_of_week' => json_encode($days),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $task->update(['recurrent_task_id' => $recurrent->id]);

        return $recurrent;
    }

    /**
     * Count active recurrent tasks (end_date >= today).
     *
     * @param Carbon $today
     * @return int
     */
    public function countActive(Carbon $today): int
    {
        return RecurrentTask::where('end_date', '>=', $today)->count();
    }
}
