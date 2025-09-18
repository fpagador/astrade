<?php

namespace Database\Factories;

use App\Enums\ContractType;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $password = 'TestPass2025!';
        return [
            'name' => $this->faker->firstName,
            'surname' => $this->faker->lastName,
            'dni' => $this->faker->unique()->regexify('[0-9]{8}[A-Z]'),
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->userName,
            'phone' => $this->faker->randomElement(['6','7']) . $this->faker->numerify('########'),
            'password' => bcrypt($password),
            'photo' => null,
            'work_schedule' => $this->faker->randomElement(['Mañana', 'Tarde', 'Noche']),
            'contract_type' => $this->faker->randomElement([ContractType::PERMANENT->value, ContractType::TEMPORARY->value]),
            'contract_start_date' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'notification_type' => $this->faker->randomElement([NotificationType::NONE->value, NotificationType::VISUAL->value, NotificationType::VISUAL_AUDIO->value]),
            'can_receive_notifications' => $this->faker->boolean(80),
            'role_id' => 3,
        ];

    }
}
