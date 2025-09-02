<?php

namespace App\Repositories;

use App\Models\UserVacation;
use App\Models\WorkCalendarDay;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository class for handling calendar-related data persistence.
 */
class CalendarRepository
{
    /**
     * Get vacations for a given user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getVacationsByUser(int $userId): Collection
    {
        return UserVacation::where('user_id', $userId)->get();
    }

    /**
     * Store a new vacation day for the user.
     *
     * @param array $data
     * @return UserVacation
     */
    public function createVacation(array $data): UserVacation
    {
        return UserVacation::create($data);
    }

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
}
