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
                return $this->render(null, "No hay tareas programadas para los días de compensación $offset");
            }

            return $this->render($tasks);
        }, 'Error al obtener tareas por día de compensación', $request);
    }
}
