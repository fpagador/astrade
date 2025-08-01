<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'title' => $this->faker->sentence,
            'body' => $this->faker->paragraph,
            'scheduled_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'delivered' => $this->faker->boolean(70), // 70% chance it’s delivered
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
