<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'visual_enabled' => $this->faker->boolean,
            'audio_enabled' => $this->faker->boolean,
            'push_enabled' => $this->faker->boolean,
            'time_window_start' => $this->faker->time(),
            'time_window_end' => $this->faker->time(),
        ];
    }
}
