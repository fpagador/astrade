<?php

namespace App\Exports;

use App\Services\TaskService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Class TaskLogExport
 *
 * Exporta usuarios con sus tareas y subtareas a Excel.
 */
class TaskLogExport implements FromCollection, WithHeadings, ShouldAutoSize
{

    /**
     * @var TaskService
     */
    protected TaskService $taskService;

    /**
     * Filtros aplicados
     *
     * @var array
     */
    protected array $filters;

    /**
     * Nombre del archivo
     *
     * @var string
     */
    private string $fileName = 'task_logs.xlsx';

    /**
     * TaskLogExport constructor.
     *
     * @param TaskService $taskService
     * @param array       $filters
     */
    public function __construct(TaskService $taskService, array $filters = [])
    {
        $this->taskService = $taskService;
        $this->filters = $filters;
    }

    /**
     * Colección de datos a exportar.
     *
     * @return Collection
     */
    public function collection()
    {
        $users = $this->taskService->getProcessedUsersWithTasks(
            $this->filters['user_name'] ?? null,
            $this->filters['task_title'] ?? null,
            $this->filters['status'] ?? null,
            $this->filters['date'] ?? null
        );

        $rows = collect();

        foreach ($users as $user) {
            foreach ($user->tasks as $task) {
                if ($task->subtasks->count() > 0) {
                    foreach ($task->subtasks as $subtask) {
                        $rows->push([
                            'Usuario'      => $user->name . ' ' . $user->surname,
                            'Tarea'        => $task->title,
                            'Tarea Estado' => $task->status_label,
                            'Subtarea'     => $subtask->title,
                            'Subtarea Estado' => $subtask->status_label,
                            'Fecha'        => $task->scheduled_date ? $task->scheduled_date : '',
                            'Hora'         => $task->scheduled_time ? $task->scheduled_time : '',
                        ]);
                    }
                } else {
                    $rows->push([
                        'Usuario'      => $user->name . ' ' . $user->surname,
                        'Tarea'        => $task->title,
                        'Tarea Estado' => $task->status_label,
                        'Subtarea'     => '',
                        'Subtarea Estado' => '',
                        'Fecha'        => $task->scheduled_date ? $task->scheduled_date : '',
                        'Hora'         => $task->scheduled_time ? $task->scheduled_time : '',
                    ]);
                }
            }
        }

        return $rows;
    }

    /**
     * Cabeceras del Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Usuario',
            'Tarea',
            'Estado Tarea',
            'Subtarea',
            'Estado Subtarea',
            'Fecha',
            'Hora',
        ];
    }
}
