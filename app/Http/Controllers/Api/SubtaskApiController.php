<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SubtaskService;

/**
 * @OA\Tag(
 *     name="Subtasks",
 *     description="Endpoints for managing subtasks"
 * )
 *
 * Controller to handle Subtask API endpoints.
 */
class SubtaskApiController extends ApiController
{
    protected SubtaskService $service;

    public function __construct(SubtaskService $service)
    {
        $this->service = $service;
    }
    /**
     * Get subtasks of a task, sorted by order.
     *
     * @param int $taskId
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/{task_id}/subtasks",
     *     summary="Get all subtasks for a task",
     *     tags={"Subtasks"},
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
     *         description="List of subtasks",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function index(int $taskId): JsonResponse
    {
        return $this->handleApi(function () use ($taskId) {
            return $this->render($this->service->getSubtasks($taskId));
        }, 'Error getting subtasks');
    }

    /**
     * Update the status of a subtask (completed or pending).
     *
     * Rules:
     * - Only the owner of the parent task can update the subtask.
     * - If all subtasks are completed → parent task becomes completed.
     * - If any subtask is pending → parent task becomes pending.
     *
     * @param Request $request
     * @param int $subtaskId
     * @param string $status
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/subtasks/{subtask_id}/status/{status}",
     *     summary="Update subtask status",
     *     tags={"Subtasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subtask_id",
     *         in="path",
     *         description="ID of the subtask",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             example=23
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="path",
     *         description="New status of the subtask",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"completed","pending"},
     *             example="pending"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subtask status updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid status"
     *     )
     * )
     */
    public function updateStatus(Request $request, int $subtaskId, string $status): JsonResponse
    {
        return $this->handleApi(function () use ($request, $subtaskId, $status) {
            $updated = $this->service->updateStatusById($subtaskId, $status, $request->user()->id);

            return $this->render($updated, "Subtask status updated to $status");
        }, 'Error updating subtask status', $request);
    }

}
