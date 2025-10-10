<?php

namespace App\Services;

use App\Enums\CalendarType;
use App\Enums\RoleEnum;
use App\Enums\TaskStatus;
use App\Repositories\CompanyRepository;
use App\Repositories\RecurrentTaskRepository;
use App\Repositories\SubtaskRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserAbsenceRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkCalendarDayRepository;
use App\Repositories\WorkCalendarTemplateRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service class responsible for handling Dashboard logic.
 * It abstracts business rules and repository calls.
 */
class DashboardService
{
    /**
     * UserTaskService constructor.
     *
     * @param UserRepository $userRepository
     * @param TaskRepository $taskRepository
     * @param UserAbsenceRepository $userAbsenceRepository
     * @param WorkCalendarTemplateRepository $workCalendarTemplateRepository
     * @param RecurrentTaskRepository $recurrentTaskRepository
     * @param SubtaskRepository $subtaskRepository
     * @param CompanyRepository $companyRepository
     * @param WorkCalendarDayRepository $workCalendarDayRepository
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected TaskRepository $taskRepository,
        protected SubtaskRepository $subtaskRepository,
        protected UserAbsenceRepository $userAbsenceRepository,
        protected RecurrentTaskRepository $recurrentTaskRepository,
        protected WorkCalendarTemplateRepository $workCalendarTemplateRepository,
        protected CompanyRepository $companyRepository,
        protected WorkCalendarDayRepository $workCalendarDayRepository
    ) {}

    /**
     * Retrieve all dashboard KPIs and necessary data.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth = $today->copy()->endOfMonth();
        $year = $today->year;
        $month = $today->month;
        $nextMonthStart = $today->copy()->addMonthNoOverflow()->startOfMonth();
        $nextMonthEnd   = $today->copy()->addMonthNoOverflow()->endOfMonth();
        $sevenDaysLater = $today->copy()->addDays(6);

        // --- USERS ---
        $totalUsers = $this->userRepository->countAll() ?? 0;
        $usersWithoutCalendar = $this->userRepository->countWithoutCalendar() ?? 0;
        $usersManagement = $this->userRepository->countByRoles([RoleEnum::ADMIN->value, RoleEnum::MANAGER->value]);
        $usersMobile = $this->userRepository->countByRoles([RoleEnum::USER->value]);

        // --- TASKS ---
        $tasksToday = $this->taskRepository->countByDate($today);
        $tasksTomorrow = $this->taskRepository->countByDate($tomorrow);
        $pendingTasks = $this->taskRepository->countByStatus(TaskStatus::PENDING->value);
        $usersWithPendingTasks = $this->userRepository->countWithPendingTasks();

        // --- RECURRENT TASKS ---
        $recurrentTasks = $this->recurrentTaskRepository->countActive($today);

        // --- DELAYED SUBTASKS ---
        $delayedSubtasks = $this->subtaskRepository->countDelayed($today);

        // --- COMPANIES ---
        $totalCompanies = $this->companyRepository->countAll();

        // --- ACTIVE WORK CALENDARS ---
        $activeCalendars = $this->workCalendarTemplateRepository->countActive();

        // --- CALENDAR DAYS (holidays) ---
        $template = $this->workCalendarTemplateRepository->getActiveTemplateForYear($year);
        $calendarDaysThisMonth = $this->workCalendarDayRepository->getDaysByMonth($year, $month, $template);
        $calendarDaysNextMonth = $this->workCalendarDayRepository->getDaysByMonth(
            $nextMonthStart->year,
            $nextMonthStart->month,
            $template
        );

        // --- USER ABSENCES (vacations/legal absence) ---
        $userVacationsThisMonth = $this->userAbsenceRepository
            ->getAbsencesGroupedByUser($startMonth, $endMonth, CalendarType::VACATION->value);
        $userLegalAbsencesThisMonth = $this->userAbsenceRepository
            ->getAbsencesGroupedByUser($startMonth, $endMonth, CalendarType::LEGAL_ABSENCE->value);
        $userVacationsNextMonth = $this->userAbsenceRepository
            ->getAbsencesGroupedByUser($nextMonthStart, $nextMonthEnd, CalendarType::VACATION->value);
        $userLegalAbsencesNextMonth = $this->userAbsenceRepository
            ->getAbsencesGroupedByUser($nextMonthStart, $nextMonthEnd, CalendarType::LEGAL_ABSENCE->value);

        // --- GRAPH DATA ---
        $tasksByDay = $this->getTasksGroupedByDay();
        $usersWithoutTasksByDay=  $this->userRepository->countUsersWithoutTasksByDay($today, $sevenDaysLater);
        $employeesByCompany = $this->userRepository->getUsersGroupedByCompany();

        return compact(
            'totalUsers',
            'usersWithoutCalendar',
            'usersManagement',
            'usersMobile',
            'tasksToday',
            'tasksTomorrow',
            'pendingTasks',
            'usersWithPendingTasks',
            'recurrentTasks',
            'delayedSubtasks',
            'totalCompanies',
            'activeCalendars',
            'calendarDaysThisMonth',
            'calendarDaysNextMonth',
            'userVacationsThisMonth',
            'userLegalAbsencesThisMonth',
            'userVacationsNextMonth',
            'userLegalAbsencesNextMonth',
            'usersWithPendingTasks',
            'tasksByDay',
            'usersWithoutTasksByDay',
            'employeesByCompany'
        );
    }

    /**
     * Get tasks grouped by day in a given month.
     *
     * @return array<string,int>
     */
    public function getTasksGroupedByDay(): array
    {
        $startDay = now()->startOfDay();
        $endDay = now()->copy()->addDays(6)->endOfDay();
        $tasksByDay = $this->taskRepository->getTasksCountGroupedByDay($startDay, $endDay);

        $tasksByDayFormatted = [];
        for ($date = $startDay->copy(); $date->lte($endDay); $date->addDay()) {
            $formattedDate = $date->format('d-m-Y');
            $tasksByDayFormatted[$formattedDate] = $tasksByDay[$date->format('Y-m-d')] ?? 0;
        }
        return $tasksByDayFormatted;
    }

    /**
     * Get users without pending tasks.
     *
     * @return Collection
     */
    public function getUsersWithoutTasks(): Collection
    {
        return $this->userRepository->getUsersWithoutTasks();
    }

    /**
     * Get users without any tasks scheduled for a specific date.
     *
     * @param Carbon $date
     * @return Collection
     */
    public function getUsersWithoutTasksForDay(Carbon $date): Collection
    {
        return $this->userRepository->getUsersWithoutTasksForDay($date);
    }

    /**
     * Get number of employees grouped by company.
     *
     * @return Collection
     */
    public function getEmployeesByCompany(?int $companyId = null): Collection
    {
        return $this->userRepository->getUsersByCompany($companyId);
    }
}
