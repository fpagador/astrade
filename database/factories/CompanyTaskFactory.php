<?php
namespace Database\Factories;

use App\Models\CompanyTask;
use App\Models\Task;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyTaskFactory extends Factory
{
    protected $model = CompanyTask::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'location_id' => Company::factory(),
        ];
    }
}
