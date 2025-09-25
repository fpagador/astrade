<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TaskService;

/**
 * @OA\Tag(name="Tasks", description="Endpoints for managing tasks")
 */
class TaskApiController extends ApiController
{
    protected TaskService $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }
    /**
     * Get all tasks and their subtasks for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Get all tasks with subtasks for the authenticated user",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of tasks with subtasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function allTasksWithSubtasks(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $tasks = $this->service->getAllTasks($request->user()->id);

            return $this->render($tasks);
        }, 'Error al obtener tareas con subtareas', $request);
    }

    /**
     * Returns the tasks scheduled for today for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/today",
     *     summary="Get today's tasks for the authenticated user",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of today's tasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function tasksToday(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $tasks = $this->service->getTodayTasks($request->user()->id);
            if ($tasks->isEmpty()) {
                return $this->render(null, 'No tasks for today');
            }
            return $this->render($tasks);

        }, "Error al obtener las tareas de hoy", $request);
    }

    /**
     * Returns the tasks planned for the next N days (maximum 30).
     *
     * @param Request $request
     * @param int $days
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/planned/{days}",
     *     summary="Get planned tasks for the next N days",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="path",
     *         description="Number of days to look ahead (max 30)",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of planned tasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function plannedTasks(Request $request, int $days): JsonResponse
    {
        return $this->handleApi(function () use ($days, $request) {
            $days = min($days, 30);
            $tasks = $this->service->getPlannedTasks($request->user()->id, $days);
            if ($tasks->isEmpty()) {
                return $this->render(null, "No tasks scheduled for the next $days days");
            }

            return $this->render($tasks);
        }, 'Error al obtener tareas programadas', $request);
    }

    /**
     * Displays the details of a task for the authenticated user.
     *
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/{task_id}",
     *     summary="Get details of a specific task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="task_id",
     *         in="path",
     *         description="ID of the task",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task details with subtasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function show(Request $request, int $taskId): JsonResponse
    {
        return $this->handleApi(function () use ($taskId, $request) {
            $task = $this->service->getTaskDetails($request->user()->id, $taskId);

            return $this->render($task);
        }, 'Error al obtener los detalles de la tarea', $request);
    }

    /**
     * Returns a summary of the status of the user's tasks.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/status/summary",
     *     summary="Get a summary of task statuses",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Summary of completed and pending tasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function statusSummary(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $userId = $request->user()->id;

            return $this->render([
                'completed' => Task::where('user_id', $userId)
                    ->where('status', 'completed')->count(),
                'pending' => Task::where('user_id', $userId)
                    ->where('status', 'pending')->count()
            ]);
        }, 'Error al obtener el resumen de la tarea', $request);
    }

    /**
     * View companies associated with a specific task via URL param.
     *
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/{taskId}/companies",
     *     summary="Get company associated with a specific task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         description="ID of the task",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company details",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(response=404, description="Task not found or not authorized"),
     *     @OA\Response(response=400, description="No company associated with this task")
     * )
     */
    public function getCompaniesByTask(Request $request, int $taskId): JsonResponse
    {
        return $this->handleApi(function () use ($request, $taskId) {
            $task = $this->service->getTaskWithCompany($request->user()->id, $taskId);
            return $this->render($task->user->company);
        }, "Error al obtener la empresa de la tarea", $request);
    }

    /**
     * Returns the tasks scheduled for a specific date for the authenticated user.
     *
     * @param Request $request
     * @param string $date Date in YYYY-MM-DD format
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/by-date/{date}",
     *     summary="Get tasks for a specific date",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="path",
     *         description="Date in YYYY-MM-DD format",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-09-25")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of tasks for the given date",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date format"
     *     )
     * )
     */
    public function tasksByDate(Request $request, string $date): JsonResponse
    {
        return $this->handleApi(function () use ($request, $date) {
            $tasks = $this->service->getTasksByDate($request->user()->id, $date);

            if ($tasks->isEmpty()) {
                return $this->render(null, "No tasks scheduled for $date");
            }

            return $this->render($tasks);
        }, 'Error al obtener tareas por fecha', $request);
    }

    /**
     * Returns the tasks for a specific day offset for the authenticated user.
     *
     * @param Request $request
     * @param int $offset Number of days from today (0 = today, 1 = tomorrow, ...)
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/day-offset/{offset}",
     *     summary="Get tasks for a specific day offset",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="offset",
     *         in="path",
     *         description="Number of days from today (0 = today)",
     *         required=true,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of tasks for the given day",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid offset"
     *     )
     * )
     */
    public function tasksByDayOffset(Request $request, int $offset): JsonResponse
    {
        return $this->handleApi(function () use ($request, $offset) {
            $offset = max(0, $offset);
            $tasks = $this->service->getTasksByDayOffset($request->user()->id, $offset);

            if ($tasks->isEmpty()) {
                return $this->render(null, "No tasks scheduled for day offset $offset");
            }

            return $this->render($tasks);
        }, 'Error al obtener tareas por día de compensación', $request);
    }
}
