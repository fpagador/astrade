<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CalendarService;

class CalendarApiController extends ApiController
{
    protected CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }
    /**
     * Returns the vacation, holiday, or sick_leave days recorded for the authenticated user.
     *
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/calendar/{type}",
     *     summary="Get user calendar days by type",
     *     tags={"Calendar"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Type of calendar days: vacation, holiday",
     *         required=true,
     *         @OA\Schema(type="string", enum={"vacation","holiday"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Calendar days retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vacation days retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="date", type="string", format="date", example="2025-09-01"),
     *                     @OA\Property(property="description", type="string", example="Family vacation")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getCalendarByType(Request $request, string $type): JsonResponse
    {
        return $this->handleApi(function () use ($request, $type) {
            $data = $this->calendarService->getCalendarByType($request->user(), $type);
            return $this->render($data);
        }, 'Error getting ' . $type . ' from user', $request);
    }
}
