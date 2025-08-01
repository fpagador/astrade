<?php
namespace Database\Factories;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    public function definition(): array
    {
        $dayTypes = ['holiday', 'vacation', 'weekend', 'leave', 'workday'];
        $types = ['holiday', 'vacation', 'sick_leave', 'weekend'];

        return [
            'user_id' => User::factory(),
            'date' => $this->faker->date(),
            'day_type' => $this->faker->randomElement($dayTypes),
            'reason' => $this->faker->optional()->sentence,
            'type' => $this->faker->randomElement($types),
            'description' => $this->faker->optional()->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
