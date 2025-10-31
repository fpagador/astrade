<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use App\Exports\TaskLogExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends WebController
{
    /**
     * Constructor
     *
     * @param TaskService $taskService
     */
    public function __construct(
        protected TaskService $taskService,
    ) {}

    /**
     * Displays the list of users with their filtered tasks.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $userName  = $request->input('user_name');
        $taskTitle = $request->input('task_title');
        $status    = $request->input('status');

        // Fechas de filtro
        $today = now()->toDateString();
        $twoMonthsAgo = now()->subMonths(2)->toDateString();
        $dateStart = $request->input('date_start', $twoMonthsAgo);
        $dateEnd   = $request->input('date_end', $today);

        if (Carbon::parse($dateStart)->diffInDays(Carbon::parse($dateEnd)) > 62) {
            $dateStart = $twoMonthsAgo;
            $dateEnd = $today;
        }

        $users = $this->taskService->getProcessedUsersWithTasks(
            $userName,
            $taskTitle,
            $status,
            $dateStart,
            $dateEnd
        );

        return view('web.admin.tasks.index', [
            'users' => $users,
            'filters' => [
                'user_name' => $userName,
                'task_title' => $taskTitle,
                'status' => $status,
                'date_start' => $dateStart,
                'date_end'   => $dateEnd,
            ],
        ]);
    }

    /**
     * Export the list of users with their filtered tasks.
     *
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->all();
        $fileName = 'Registro de tareas por usuario '. now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TaskLogExport($this->taskService, $filters), $fileName);
    }
}
