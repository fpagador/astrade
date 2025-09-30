<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Repositories\TaskRepository;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
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
     * @return View
     */
    public function index(): View
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
        $date = Carbon::createFromFormat('d/m', $day);
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
     * @return JsonResponse
     */
    public function employeesByCompany(): JsonResponse
    {
        $data = $this->dashboardService->getEmployeesByCompany();
        return response()->json($data);
    }
}
