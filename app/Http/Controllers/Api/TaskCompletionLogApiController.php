<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TaskCompletionLogService;

class TaskCompletionLogApiController extends ApiController
{
    protected TaskCompletionLogService $service;

    public function __construct(TaskCompletionLogService $service)
    {
        $this->service = $service;
    }

    /**
     * Retrieve the history of completed tasks and subtasks for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/logs/completions",
     *     summary="Get completed tasks and subtasks history",
     *     tags={"Logs"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Task completion logs retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(response=404, description="No task completion logs found")
     * )
     */
    public function taskCompletions(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $logs = $this->service->getUserTaskCompletions($request->user()->id);

            return $this->render($logs, 'Task completion history retrieved successfully');
        }, 'Failed to fetch task completion history', $request);
    }
}
