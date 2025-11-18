<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Services\TaskService;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use App\Exports\TaskLogExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $filters = $this->taskService->getFilters($request);
        $users = $this->taskService->getProcessedUsersWithTasks(
            $filters['user_name'],
            $filters['task_title'],
            $filters['status'],
            $filters['date_start'],
            $filters['date_end']
        );

        return view('web.admin.tasks.index', [
            'users' => $users,
            'filters' => [
                'user_name' => $filters['user_name'],
                'task_title' => $filters['task_title'],
                'status' => $filters['status'],
                'date_start' => $filters['date_start'],
                'date_end'   => $filters['date_end'],
            ],
        ]);
    }

    /**
     * Exports a list of tasks filtered by user in CSV format.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->taskService->getFilters($request);
        $fileName = 'registro_tareas_usuarios_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Cache-Control"       => "no-store, no-cache, must-revalidate",
            "Pragma"              => "no-cache",
        ];

        return response()->stream(function () use ($filters) {

            $handle = fopen('php://output', 'w');

            // BOM UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            //CSV Headers
            fputcsv($handle, [
                'Usuario',
                'Tarea',
                'Estado Tarea',
                'Subtarea',
                'Estado Subtarea',
                'Fecha',
                'Hora',
            ], ';');

            $this->taskService->streamTasksCsv($filters, $handle);
            fclose($handle);

        }, 200, $headers);
    }
}
