<?php
namespace Database\Factories;

use App\Models\LocationTask;
use App\Models\Task;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationTaskFactory extends Factory
{
    protected $model = LocationTask::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'location_id' => Location::factory(),
        ];
    }
}
