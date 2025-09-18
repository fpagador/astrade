<?php

namespace App\Repositories;

use App\Enums\CalendarType;
use App\Models\UserAbsence;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository class for handling calendar-related data persistence.
 */
class UserAbsenceRepository
{
    /**
     * Get vacations for a given user.
     *
     * @param int $userId
     * @param string $type
     * @return Collection
     */
    public function getAbsenceByUser(int $userId, string $type): Collection
    {
        return UserAbsence::where('user_id', $userId)->where('type', $type)->get();
    }

    /**
     * Delete absences of a given type for a user.
     *
     * @param int $userId
     * @param string $type
     * @return void
     */
    public function deleteByUserAndType(int $userId, string $type): void
    {
        UserAbsence::where('user_id', $userId)->where('type', $type)->delete();
    }

    /**
     * Create a new absence record.
     *
     * @param array $data
     * @return UserAbsence
     */
    public function create(array $data): UserAbsence
    {
        return UserAbsence::create($data);
    }

    /**
     * Get all absences for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserAbsences(int $userId): Collection
    {
        return UserAbsence::where('user_id', $userId)->get();
    }

    /**
     * Check if the user has a vacation or legal absence on a given date.
     *
     * @param int $userId
     * @param string $date
     * @return bool
     */
    public function hasAbsence(int $userId, string $date): bool
    {
        return UserAbsence::where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereIn('type', [CalendarType::HOLIDAY->value, CalendarType::LEGAL_ABSENCE->value])
            ->exists();
    }
}
