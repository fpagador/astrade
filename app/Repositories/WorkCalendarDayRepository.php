<?php

namespace App\Repositories;

use App\Enums\CalendarType;
use App\Models\WorkCalendarDay;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
     * Determine if a given date is marked as a holiday for the specified calendar template.
     *
     * @param int    $templateId
     * @param string $date
     * @return bool
     */
    public function isHolidayForTemplate(int $templateId, string $date): bool
    {
        return WorkCalendarDay::where('template_id', $templateId)
            ->whereDate('date', $date)
            ->where('day_type', CalendarType::HOLIDAY->value)
            ->exists();
    }
}
