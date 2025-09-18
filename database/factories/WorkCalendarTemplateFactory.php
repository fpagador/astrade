<?php
namespace Database\Factories;

use App\Models\WorkCalendarTemplate;
use App\Models\WorkCalendarDay;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkCalendarTemplateFactory  extends Factory
{
    protected $model = WorkCalendarTemplate::class;

    public function definition(): array
    {
        $year = $this->faker->year();

        return [
            'name' => 'Calendario ' . $this->faker->city(),
            'year' => $year,
            'status' => $this->faker->randomElement(['draft', 'active', 'inactive']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withDays(int $month = 1)
    {
        return $this->afterCreating(function (WorkCalendarTemplate $template) use ($month) {
            $start = Carbon::create($template->year, $month, 1);
            $end = $start->copy()->endOfMonth();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dayType = 'holiday';

                WorkCalendarDay::create([
                    'template_id' => $template->id,
                    'date' => $date->toDateString(),
                    'day_type' => $dayType
                ]);
            }
        });
    }
}
