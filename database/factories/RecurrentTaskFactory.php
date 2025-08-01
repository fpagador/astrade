<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RecurrentTask;
use App\Models\User;

class RecurrentTaskFactory extends Factory
{
    protected $model = RecurrentTask::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->optional()->date(),
            'days_of_week' => 'Mon,Wed,Fri',
        ];
    }
}
