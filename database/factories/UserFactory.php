<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName,
            'surname' => $this->faker->lastName,
            'dni' => $this->faker->unique()->regexify('[0-9]{8}[A-Z]'),
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->userName,
            'password' => bcrypt('password'),
            'photo' => null,
            'work_schedule' => $this->faker->randomElement(['Mañana', 'Tarde', 'Noche']),
            'contract_type' => $this->faker->randomElement(['fijo', 'temporal']),
            'contract_start_date' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'notification_type' => $this->faker->randomElement(['none', 'visual', 'visual_audio']),
            'can_receive_notifications' => $this->faker->boolean(80),
            'role_id' => 3,
        ];

    }
}
