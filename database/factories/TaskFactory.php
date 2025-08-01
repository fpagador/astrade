<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Task;
use App\Models\User;
use App\Models\RecurrentTask;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed']);

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'assigned_by' => User::inRandomOrder()->first()->id,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'scheduled_date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'scheduled_time' => $this->faker->time('H:i'),
            'estimated_duration_minutes' => $this->faker->numberBetween(15, 180),
            'pictogram_path' => null,
            'order' => $this->faker->numberBetween(1, 10),
            'status' => $status,
            'recurrent_task_id' => null,
        ];
    }
}
