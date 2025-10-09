<?php

namespace App\Repositories;

use App\Enums\CalendarType;
use App\Models\WorkCalendarDay;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use App\Models\WorkCalendarTemplate;

/**
 * Repository class for handling calendar-related data persistence.
 */
class WorkCalendarDayRepository
{
    /**
     * Get calendar days (holiday, sick_leave, etc.) from the work calendar template.
     *
     * @param int $templateId
     * @param string $type
     * @return Collection
     */
    public function getCalendarDaysByTemplate(int $templateId, string $type): Collection
    {
        return WorkCalendarDay::where('template_id', $templateId)
            ->where('day_type', $type)
            ->get();
    }

    /**
     * Insert many days for a calendar template.
     *
     * @param array $days
     * @return void
     */
    public function insertDays(array $days): void
    {
        WorkCalendarDay::insert($days);
    }

    /**
     * Delete days of a template for a specific year.
     *
     * @param int $templateId
     * @param int $year
     * @return void
     */
    public function deleteDaysByYear(int $templateId, int $year): void
    {
        WorkCalendarDay::where('template_id', $templateId)
            ->whereYear('date', $year)
            ->delete();
    }

    /**
     * Delete day of a template
     *
     * @param WorkCalendarDay $day
     * @return void
     */
    public function removeDay(WorkCalendarDay $day): void
    {
        $day->delete();
    }

    /**
     * Get holidays (day_type = 'holiday') for a template.
     *
     * @param int $templateId
     * @param string $format 'collection' | 'array'
     * @return Collection|array
     */
    public function getHolidaysByTemplate(int $templateId, string $format = 'collection'): Collection|array
    {
        $query = WorkCalendarDay::where('template_id', $templateId)
            ->where('day_type', CalendarType::HOLIDAY->value);

        if ($format === 'collection') {
            return $query->get();
        }

        if ($format === 'array') {
            return $query->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->toArray();
        }

        throw new \InvalidArgumentException("Formato no soportado: {$format}");
    }

    /**
     * Get active calendar days of the month
     *
     * @param int $year
     * @param int $month
     * @param ?WorkCalendarTemplate $template
     * @return Collection
     */
    public function getDaysByMonth(int $year, int $month, ?WorkCalendarTemplate $template): Collection
    {
        if (!$template) {
            return new Collection();
        }

        return WorkCalendarDay::where('template_id', $template->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(fn($day) => $day->date->format('Y-m-d'));
    }
}
