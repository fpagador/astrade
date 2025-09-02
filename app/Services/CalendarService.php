<?php

namespace App\Services;

use App\Repositories\CalendarRepository;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Collection;
use App\Models\UserVacation;
use App\Models\User;

/**
 * Service class for calendar business logic.
 */
class CalendarService
{
    protected CalendarRepository $calendarRepository;

    /**
     * CalendarService constructor.
     *
     * @param CalendarRepository $calendarRepository
     */
    public function __construct(CalendarRepository $calendarRepository)
    {
        $this->calendarRepository = $calendarRepository;
    }

    /**
     * Get user calendar days depending on the requested type.
     *
     * @param User $user
     * @param string $type
     * @return Collection
     * @throws BusinessRuleException
     */
    public function getCalendarByType(User $user, string $type): Collection
    {
        if ($type === 'vacation') {
            $vacations = $this->calendarRepository->getVacationsByUser($user->id);

            if ($vacations->isEmpty()) {
                throw new BusinessRuleException('No vacation days recorded for this user', 404);
            }

            return $vacations;
        }

        return $this->calendarRepository->getCalendarDaysByTemplate(
            $user->work_calendar_template_id,
            $type
        );
    }

    /**
     * Store a new vacation day for the authenticated user.
     *
     * @param User $user
     * @param array $data
     * @return UserVacation
     */
    public function storeVacation(User $user, array $data): UserVacation
    {
        $data['user_id'] = $user->id;

        return $this->calendarRepository->createVacation($data);
    }
}
