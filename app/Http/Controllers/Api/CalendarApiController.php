<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Models\Calendar;
use App\Http\Requests\Calendar\VacationRequest;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use App\Http\Requests\Calendar\CalendarTypeRequest;

class CalendarApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Returns the vacation, holiday or sick_leave days recorded for the authenticated user
     *
     * @param CalendarTypeRequest $request
     * @param string $type
     * @return JsonResponse
     */
    public function getCalendarByType(CalendarTypeRequest $request, string $type)
    {
        return $this->handleApi(function () use ($request, $type) {
            $calendarType = Calendar::where('user_id', $request->user()->id)
                ->where('type', $type)->get();

            return $this->render($calendarType);
        }, 'Error getting ' . $type . ' from user', $request);
    }

    /**
     * Registers a new vacation for the authenticated user.
     *
     * @param VacationRequest $request
     * @return JsonResponse
     */
    public function storeVacation(VacationRequest $request)
    {
        return $this->handleApi(function () use ($request) {
            $data = $request->validated();

            $vacation = $request->user()->calendar()->create([
                'date' => $data['start_date'],
                'day_type' => 'vacation',
                'reason' => $data['reason'] ?? null,
                'type' => 'vacation',
                'description' => 'Registered vacation',
            ]);

            return $this->render($vacation, 'Vacation successfully recorded');
        }, 'Error registering vacation', $request);
    }

}
