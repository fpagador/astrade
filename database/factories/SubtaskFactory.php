<?php

namespace Database\Factories;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubtaskFactory extends Factory
{
    protected $model = Subtask::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed']);

        return [
            'task_id' => Task::inRandomOrder()->first()->id,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'note' => $this->faker->optional()->sentence(),
            'order' => $this->faker->numberBetween(1, 5),
            'status' => $status,
        ];
    }
}
