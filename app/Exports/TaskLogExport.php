<?php

namespace App\Exports;

use App\Services\TaskService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Carbon;

/**
 * Class TaskLogExport
 *
 * Exporta usuarios con sus tareas y subtareas a Excel.
 */
class TaskLogExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
                            'Fecha'        => $task->scheduled_date
                                ? Carbon::parse($task->scheduled_date)->format('d/m/Y')
                                : '—',
                            'Hora'         => $task->scheduled_time
                                ? Carbon::parse($task->scheduled_time)->format('H:m:s')
                                : '—',
                        ]);
                    }
                } else {
                    $rows->push([
                        'Usuario'      => $user->name . ' ' . $user->surname,
                        'Tarea'        => $task->title,
                        'Tarea Estado' => $task->status_label,
                        'Subtarea'     => '',
                        'Subtarea Estado' => '',
                        'Fecha'        => $task->scheduled_date
                            ? Carbon::parse($task->scheduled_date)->format('d/m/Y')
                            : '—',
                        'Hora'         => $task->scheduled_time
                            ? Carbon::parse($task->scheduled_time)->format('H:m:s')
                            : '—',
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

    /**
     * Apply basic styling to the Excel sheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'A06CD5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
            ],

        ]);

        return [];
    }
}
