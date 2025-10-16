<?php
namespace Database\Factories;

use App\Enums\CalendarStatus;
use App\Models\WorkCalendarTemplate;
use App\Models\WorkCalendarDay;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkCalendarTemplateFactory  extends Factory
{
    protected $model = WorkCalendarTemplate::class;

    public function definition(): array
    {
        $currentYear = Carbon::today()->year;
        $year = $this->faker->numberBetween($currentYear - 1, $currentYear + 1);

        return [
            'name' => 'Calendario ' . $this->faker->city(),
            'year' => $year,
            'status' => $this->faker->randomElement([CalendarStatus::DRAFT->value, CalendarStatus::ACTIVE->value, CalendarStatus::INACTIVE->value]),
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
