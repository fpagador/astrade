<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Repositories\TaskRepository;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends WebController
{
    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService
     * @param TaskRepository $taskRepository
     *
     */
    public function __construct(
        protected DashboardService $dashboardService,
        protected TaskRepository $taskRepository
    ) {}

    /**
     * Display the dashboard.
     *
     * @return View| RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        return $this->tryCatch(function () {
            $data = $this->dashboardService->getDashboardData();
            return view('web.admin.dashboard', $data);
        });
    }

    /**
     * Return tasks for a given day (format: dd/mm).
     *
     * @param string $day
     * @return JsonResponse
     */
    public function tasksByDay(string $day): JsonResponse
    {
        $date = Carbon::createFromFormat('d-m-Y', $day);
        $tasks = $this->taskRepository->getTasksByDate($date);
        return response()->json($tasks);
    }

    /**
     * Return tasks for a user or users without tasks if $userId is null.
     *
     * @param int|null $userId
     * @return JsonResponse
     */
    public function tasksByUser(?int $userId): JsonResponse
    {
        if ($userId) {
            $tasks = $this->taskRepository->getTasksByUser($userId);
        } else {
            $tasks = $this->dashboardService->getUsersWithoutTasks();
        }

        return response()->json($tasks);
    }

    /**
     * Return number of employees per company.
     *
     * @param ?int $companyId
     * @return JsonResponse
     */
    public function employeesByCompany(?int $companyId = null): JsonResponse
    {
        $data = $this->dashboardService->getEmployeesByCompany($companyId);
        return response()->json($data);
    }

    /**
     * Returns users without tasks for a specific day.
     *
     * @param string $day
     * @return JsonResponse
     */
    public function usersWithoutTasks(string $day): JsonResponse
    {
        $date = Carbon::createFromFormat('Y-m-d', $day)->startOfDay();
        $usersWithoutTasks = $this->dashboardService->getUsersWithoutTasksForDay($date);
        return response()->json($usersWithoutTasks);
    }

    public function getUsersByPerformance(string $day, string $range)
    {
        $range = urldecode($range);

        $rows = $this->taskRepository->getTasksPerformanceRaw(Carbon::parse($day), Carbon::parse($day));

        $users = collect($rows)->filter(function ($r) use ($range) {
            $completed = (int)$r->completed;
            $total = (int)$r->total;
            $percentage = $total > 0 ? ($completed / $total) * 100 : 0;

            $currentRange = match (true) {
                $percentage === 100.0 => '100%',
                $percentage >= 75.0 => '75-99.9%',
                $percentage >= 50.0 => '50-74.9%',
                default => '<50%',
            };

            return $currentRange === $range;
        })->map(fn($r) => [
            'id' => $r->user_id,
            'name' => $r->name ?? '',
            'surname' => $r->surname ?? '',
        ])->values();

        return response()->json($users);
    }
}
