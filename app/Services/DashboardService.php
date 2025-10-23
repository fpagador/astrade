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
        $sevenDaysLater = $today->copy()->addWeeks(4);

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
        $usersWithoutTasksByDay=  $this->userRepository->countUsersWithoutTasksByDay($today, $sevenDaysLater);
        $usersWithTasksByDay = [];
        foreach ($usersWithoutTasksByDay as $day => $withoutTasksCount) {
            $usersWithTasksByDay[$day] = max(0, $totalUsers - $withoutTasksCount);
        }

        $tasksByDay = $this->getTasksCompletionByDay($today);

        $employeesByCompany = $this->userRepository->getUsersGroupedByCompany();

        // --- TASK PERFORMANCE HISTORY ---
        $taskPerformanceHistory = $this->getTaskPerformanceHistory();

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
            'usersWithoutTasksByDay',
            'tasksByDay',
            'employeesByCompany',
            'usersWithTasksByDay',
            'taskPerformanceHistory'
        );
    }

    /**
     * Get tasks grouped by day in a given month.
     *
     * @param Carbon $today
     * @param int $weeksForward
     * @return array<string,int>
     */
    public function getTasksCompletionByDay(Carbon $today, int $weeksForward = 4): array {
        $daysForward = $weeksForward * 7;
        $tasksByDay = [];

        for ($i = 0; $i < $daysForward; $i++) {
            $date = $today->copy()->addDays($i);
            $totalTasks = $this->taskRepository->countByDate($date);
            $completedTasks = $this->taskRepository->countByDateAndStatus($date, TaskStatus::COMPLETED->value);

            $tasksByDay[$date->format('Y-m-d')] = [
                TaskStatus::COMPLETED->value => $completedTasks,
                TaskStatus::PENDING->value => max(0, $totalTasks - $completedTasks)
            ];
        }

        return $tasksByDay;
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

    /**
     *Obtains the performance history of completed tasks per day and by ranges.
     *
     * - Default range: from yesterday to (weeksBack * 7) days ago (inclusive).
     *
     * - Defined ranges:
     *   '100%', '75-99.9%', '50-74.9%', '<50%'
     *
     * Return format:
     * [
     *   'YYYY-MM-DD' => [
     *       '100%' => float,          // % (0..100)
     *       '75-99.9%' => float,
     *       '50-74.9%' => float,
     *       '<50%' => float,
     *       'users' => [
     *           '100%' => [user_id, ...],
     *           '75-99.9%' => [...],
     *           '50-74.9%' => [...],
     *           '<50%' => [...],
     *       ]
     *   ],
     *   ...
     * ]
     * @param int $weeksBack
     * @return array
     */
    public function getTaskPerformanceHistory(int $weeksBack = 4): array
    {
        $weeksBack = max(1, (int)$weeksBack);

        $end = Carbon::yesterday()->startOfDay();
        $daysCount = $weeksBack * 7;
        $start = $end->copy()->subDays($daysCount - 1)->startOfDay();

        $rows = $this->taskRepository->getTasksPerformanceRaw($start, $end);

        $history = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $history[$key] = [
                '100%' => 0.0,
                '75-99.9%' => 0.0,
                '50-74.9%' => 0.0,
                '<50%' => 0.0,
                'users' => [
                    '100%' => [],
                    '75-99.9%' => [],
                    '50-74.9%' => [],
                    '<50%' => [],
                ],
            ];
            $cursor->addDay();
        }

        foreach ($rows as $r) {
            $day = Carbon::parse($r->scheduled_date)->format('Y-m-d');

            if (!array_key_exists($day, $history)) {
                continue;
            }

            $completed = (int)$r->completed;
            $total = (int)$r->total;
            $percentage = $total > 0 ? ($completed / $total) * 100 : 0;

            $range = match (true) {
                $percentage === 100.0 => '100%',
                $percentage >= 75.0 => '75-99.9%',
                $percentage >= 50.0 => '50-74.9%',
                default => '<50%',
            };

            if (!isset($history[$day]['__counts'])) {
                $history[$day]['__counts'] = [
                    '100%' => 0,
                    '75-99.9%' => 0,
                    '50-74.9%' => 0,
                    '<50%' => 0,
                ];
            }
            $history[$day]['__counts'][$range]++;
            $history[$day]['users'][$range][] = (int)$r->user_id;
        }

        foreach ($history as $day => &$data) {
            $counts = $data['__counts'] ?? [
                    '100%' => 0,
                    '75-99.9%' => 0,
                    '50-74.9%' => 0,
                    '<50%' => 0,
                ];

            $totalUsersWithTasksThatDay = array_sum($counts);

            if ($totalUsersWithTasksThatDay > 0) {
                foreach (['100%', '75-99.9%', '50-74.9%', '<50%'] as $key) {
                    $data[$key] = round(($counts[$key] / $totalUsersWithTasksThatDay) * 100, 2);
                }
            } else {
                foreach (['100%', '75-99.9%', '50-74.9%', '<50%'] as $key) {
                    $data[$key] = 0.0;
                }
            }

            unset($data['__counts']);

            foreach (['100%', '75-99.9%', '50-74.9%', '<50%'] as $key) {
                if (!isset($data['users'][$key])) {
                    $data['users'][$key] = [];
                }
            }
        }
        unset($data);

        return $history;
    }
}
