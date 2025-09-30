<?php

namespace App\Http\Controllers\Api;

use App\Errors\ErrorCodes;
use App\Exceptions\BusinessRuleException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CalendarService;
use App\Enums\CalendarColor;
use App\Enums\CalendarType;

class CalendarApiController extends ApiController
{
    protected CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Returns the vacation, holiday, or legal_absence days recorded for the authenticated user.
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
     *         description="Type of calendar days: vacation, holiday or legal_absence",
     *         required=true,
     *         @OA\Schema(type="string", enum={"vacation","holiday","legal_absence"})
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
     *                 type="object",
     *                 @OA\Property(
     *                     property="color",
     *                     type="object",
     *                     @OA\Property(property="class", type="string", example="bg-green-500"),
     *                     @OA\Property(property="hex", type="string", example="#22c55e")
     *                 ),
     *                 @OA\Property(
     *                     property="days",
     *                     type="array",
     *                     @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="date", type="string", format="date", example="2025-09-01"),
     *                          @OA\Property(property="description", type="string", example="Family vacation")
     *                     )
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
            // Validate that the type exists in the enum
            $typeEnum = collect(CalendarType::cases())
                ->firstWhere(fn(CalendarType $case) => $case->value === strtolower($type));

            if (!$typeEnum) {
                throw new BusinessRuleException(
                    "Invalid type: $type",
                    422,
                    ErrorCodes::INVALID_CALENDAR_TYPE,
                    'CALENDAR'
                );
            }

            $days  = $this->calendarService->getCalendarByType($request->user(), $typeEnum->value);

            $color = CalendarColor::values()[$typeEnum->name] ?? null;

            $days = $days->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date->format('Y-m-d')
                ];
            });

            $data = [
                'color' => $color,
                'days'  => $days,
            ];

            return $this->render($data);
        }, 'Error getting ' . $type . ' from user', $request);
    }

    /**
     * Returns all available calendar colors.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/calendar/colors",
     *     summary="Get all available calendar colors",
     *     tags={"Calendar"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Calendar colors retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Colors retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 additionalProperties={
     *                     @OA\Schema(
     *                         type="object",
     *                         @OA\Property(property="class", type="string", example="bg-green-500"),
     *                         @OA\Property(property="hex", type="string", example="#22c55e")
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getColors(Request $request): JsonResponse
    {
        return $this->handleApi(function () {
            $colors = CalendarColor::values();

            return $this->render($colors);
        }, 'Error getting calendar colors', $request);
    }
}
