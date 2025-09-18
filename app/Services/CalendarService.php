<?php

namespace App\Services;

use App\Enums\CalendarStatus;
use App\Enums\CalendarType;
use App\Errors\ErrorCodes;
use App\Repositories\UserAbsenceRepository;
use App\Exceptions\BusinessRuleException;
use App\Repositories\WorkCalendarDayRepository;
use App\Repositories\WorkCalendarTemplateRepository;
use App\Models\WorkCalendarTemplate;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\WorkCalendarDay;

/**
 * Service class for calendar business logic.
 */
class CalendarService
{

    /**
     * CalendarService constructor.
     *
     * @param UserAbsenceRepository $userAbsenceRepository
     * @param WorkCalendarDayRepository $workCalendarDayRepository
     * @param WorkCalendarTemplateRepository $workCalendarTemplateRepository
     */
    public function __construct(
        protected UserAbsenceRepository $userAbsenceRepository,
        protected WorkCalendarDayRepository $workCalendarDayRepository,
        protected WorkCalendarTemplateRepository $workCalendarTemplateRepository
    ) {}

    //================================ API ======================================

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
        if ($type === CalendarType::VACATION->value || $type === CalendarType::LEGAL_ABSENCE->value ) {
            $absences = $this->getAbsenceByUser($user, $type);
            if ($absences ->isEmpty()) {
                throw new BusinessRuleException(
                    'No vacation days recorded for this user',
                    404,
                    ErrorCodes::VACATION_NOT_FOUND,
                    'CALENDAR'
                );
            }
            return $absences ;
        }

        if (!$user->work_calendar_template_id) {
            throw new BusinessRuleException(
                "User does not have a work calendar template assigned",
                404,
                ErrorCodes::HOLIDAY_NOT_FOUND,
                'CALENDAR'
            );
        }

        // Retrieve holidays from work calendar template
        return $this->workCalendarDayRepository->getCalendarDaysByTemplate(
            $user->work_calendar_template_id,
            'holiday'
        );
    }

    /**
     * Get absences of a given type for a specific user.
     *
     * @param User $user
     * @param string $type
     * @return Collection
     */
    public function getAbsenceByUser(User $user, string $type): Collection
    {
        return $this->userAbsenceRepository->getAbsenceByUser($user->id, $type);
    }

    //================================ WEB ======================================

    /**
     * Retrieve a work calendar template by its ID.
     *
     * @param int $id
     * @return WorkCalendarTemplate
     */
    public function getWorkCalendarTemplateById(int $id): WorkCalendarTemplate
    {
        return $this->workCalendarTemplateRepository->getWorkCalendarTemplateById($id);
    }

    /**
     * Save user absences for a given type (vacation or legal absence).
     *
     * @param User $user
     * @param string $type
     * @param array $dates
     * @return void
     */
    public function saveUserAbsences(User $user, string $type, array $dates): void
    {
        // Delete existing records of this type
        $this->userAbsenceRepository->deleteByUserAndType($user->id, $type);

        // Insert new records
        foreach ($dates as $date) {
            $this->userAbsenceRepository->create([
                'user_id' => $user->id,
                'date' => $date,
                'type' => $type,
            ]);
        }
    }

    /**
     * Get paginated list of work calendar templates with filters.
     *
     * @param array $filters
     * @param string $sort
     * @param string $direction
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedTemplates(array $filters = [], string $sort = 'name', string $direction = 'asc', int $perPage = 15): LengthAwarePaginator
    {
        return $this->workCalendarTemplateRepository->paginate($filters, $sort, $direction, $perPage);
    }

    /**
     * Create a new work calendar template and generate its days.
     *
     * @param array $data
     * @param string|null $holidaysJson
     * @return WorkCalendarTemplate
     */
    public function createTemplate(array $data, ?string $holidaysJson = null): WorkCalendarTemplate
    {
        $template = $this->workCalendarTemplateRepository->create($data);
        $this->generateCalendarDays($template, $holidaysJson);
        return $template;
    }

    /**
     * Update a work calendar template and regenerate its days.
     *
     * @param WorkCalendarTemplate $template
     * @param array $data
     * @param string|null $holidaysJson
     * @return bool
     */
    public function updateTemplate(WorkCalendarTemplate $template, array $data, ?string $holidaysJson = null): bool
    {
        $updated = $this->workCalendarTemplateRepository->update($template, $data);
        $this->workCalendarDayRepository->deleteDaysByYear($template->id, $template->year);
        $this->generateCalendarDays($template, $holidaysJson);
        return $updated;
    }

    /**
     * Generate weekends and holidays for a calendar template.
     *
     * @param WorkCalendarTemplate $template
     * @param string|null $holidaysJson
     * @return void
     */
    public function generateCalendarDays(WorkCalendarTemplate $template, ?string $holidaysJson): void
    {
        // Add holidays
        $holidays = json_decode($holidaysJson, true) ?? [];
        foreach ($holidays as $holiday) {
            $days[] = [
                'template_id' => $template->id,
                'date' => Carbon::parse($holiday)->format('Y-m-d H:i:s'),
                'day_type' => 'holiday',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($days)) {
            $this->workCalendarDayRepository->insertDays($days);
        }
    }

    /**
     * Delete a work calendar template and detach users.
     *
     * @param WorkCalendarTemplate $template
     * @return bool|null
     */
    public function deleteTemplate(WorkCalendarTemplate $template): ?bool
    {
        $template->users()->update(['work_calendar_template_id' => null]);
        return $this->workCalendarTemplateRepository->delete($template);
    }

    /**
     * Retrieve status options for calendar templates.
     * If a template is provided, DRAFT status is only available
     * when the template is currently in DRAFT.
     *
     * @param WorkCalendarTemplate|null $template
     * @return array
     */
    public function getStatusOptions(?WorkCalendarTemplate $template = null): array
    {
        return collect(CalendarStatus::cases())
            ->filter(function ($case) use ($template) {
                if (!$template) {
                    return true;
                }
                if ($case === CalendarStatus::DRAFT && $template->status !== CalendarStatus::DRAFT->value) {
                    return false;
                }

                return true;
            })
            ->mapWithKeys(fn($case) => [$case->value => CalendarStatus::label($case)])
            ->toArray();
    }

    /**
     * Retrieve all active Work Calendar Templates.
     *
     * @return Collection
     */
    public function getActiveTemplates(): Collection
    {
        return $this->workCalendarTemplateRepository->getActive();
    }

    /**
     * Prepare data needed for editing a calendar template.
     *
     * @param WorkCalendarTemplate $template
     * @return array
     */
    public function getTemplateForEdit(WorkCalendarTemplate $template): array
    {
        $holidays = $this->getHolidaysForArray($template);
        return [
            'template' => $template,
            'holidaysJson' => json_encode($holidays),
            'statusOptions' => $this->getStatusOptions($template),
            'existingCalendars' => $this->getActiveTemplates(),
            'holidayDates' => $holidays,
        ];
    }

    /**
     * Get the holidays
     *
     * @param WorkCalendarTemplate $template
     * @return array
     */
    public function getHolidaysForArray(WorkCalendarTemplate $template): array
    {
        return $this->workCalendarDayRepository->getHolidaysByTemplate($template->id, 'array');
    }

    /**
     * Add a new day holiday to a given calendar template.
     *
     * @param WorkCalendarTemplate $template
     * @param array $data
     * @return void
     */
    public function addDayToTemplate(WorkCalendarTemplate $template, array $data): void
    {
        $validated = validator($data, [
            'date' => 'required|date',
            'day_type' => 'required|in:holiday'
        ])->validate();

        $template->days()->create($validated);
    }

    /**
     * Prepare template data for cloning.
     *
     * @param WorkCalendarTemplate $template
     * @return array
     */
    public function getTemplateCloneData(WorkCalendarTemplate $template): array
    {
        $template->load('days');

        return [
            'name' => $template->name,
            'status' => $template->status,
            'holidays' => $template->days
                ->where('day_type', 'holiday')
                ->pluck('date')
                ->values()
        ];
    }

    /**
     * Remove a specific day from a calendar template.
     *
     * @param WorkCalendarDay $day
     * @return void
     */
    public function removeDayFromTemplate(WorkCalendarDay $day): void
    {
        $this->workCalendarDayRepository->removeDay($day);
    }
}
