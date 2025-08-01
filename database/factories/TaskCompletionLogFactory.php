<?php

namespace Database\Factories;

use App\Models\TaskCompletionLog;
use App\Models\User;
use App\Models\Task;
use App\Models\Subtask;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskCompletionLogFactory extends Factory
{
    protected $model = TaskCompletionLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'subtask_id' => Subtask::factory(),
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
