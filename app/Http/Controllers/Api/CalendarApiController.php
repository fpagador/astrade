<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use App\Models\UserVacation;
use App\Models\WorkCalendarDay;

class CalendarApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Returns the vacation, holiday or sick_leave days recorded for the authenticated user
     *
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function getCalendarByType(Request $request, string $type): JsonResponse
    {
        return $this->handleApi(function () use ($request, $type) {
            if ($type === 'vacation') {
                // Días de vacaciones del usuario
                $vacations = UserVacation::where('user_id', $request->user()->id)->get();
                return $this->render($vacations);
            }

            // Días del calendario de trabajo (según la plantilla asignada al usuario)
            $user = $request->user();
            $templateId = $user->work_calendar_template_id;

            $calendarDays = WorkCalendarDay::where('template_id', $templateId)
                ->where('day_type', $type)
                ->get();

            return $this->render($calendarDays);
        }, 'Error getting ' . $type . ' from user', $request);
    }

    /**
     * Registers a new vacation for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeVacation(Request $request)
    {
        return $this->handleApi(function () use ($request) {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'description' => 'nullable|string|max:255',
            ]);

            $vacation = UserVacation::create([
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'description' => $request->description,
            ]);

            return $this->render($vacation, 'Vacation successfully recorded');
        }, 'Error registering vacation', $request);
    }

}
