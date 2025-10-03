<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CalendarType;
use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;
use App\Services\CalendarService;

class UserAbsenceController extends WebController
{
    /**
     * Construct
     *
     * @param CalendarService $calendarService
     */
    public function __construct(
        protected CalendarService $calendarService,
    ) {}

    /**
     * Display a user's vacation and legal absence calendar.
     *
     * @param Request $request
     * @param User $user
     * @return View|RedirectResponse
     */
    public function index(Request $request, User $user): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $user) {
            // User's vacation dates
            $vacationDates = $this->calendarService->getAbsenceByUser($user, CalendarType::VACATION->value)
                ->map(fn($d) => $d->date->format('Y-m-d') ?? $d->date)
                ->toArray();

            // User's legal absence dates
            $legalAbsences = $this->calendarService->getAbsenceByUser($user, CalendarType::LEGAL_ABSENCE->value)
                ->map(fn($d) => $d->date->format('Y-m-d') ?? $d->date)
                ->toArray();

            // Active staff of the year
            $holidayDates = [];
            if ($user->work_calendar_template_id) {
                $template = $this->calendarService->getWorkCalendarTemplateById($user->work_calendar_template_id);
                $holidayDates = $this->calendarService->getHolidaysForArray($template);
            }

            $backUrl = $request->get('back_url');

            return view('web.admin.users.absences',
                compact(
                    'user',
                    'vacationDates',
                    'holidayDates',
                    'legalAbsences',
                    'backUrl'
                ));
        }, route('admin.users.index'));
    }

    /**
     * Store the selected vacation and legal absence dates for a user.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $user) {
            $vacationDates = json_decode($request->input('dates_json', '[]'), true);
            $legalDates = json_decode($request->input('legal_absences_json', '[]'), true);

            // Save user absences
            $this->calendarService->saveUserAbsences($user, CalendarType::VACATION->value, $vacationDates);
            $this->calendarService->saveUserAbsences($user, CalendarType::LEGAL_ABSENCE->value, $legalDates);

            return redirect()->route('admin.users.index',  ['type' => UserTypeEnum::MOBILE->value]);
        }, route('admin.users.index'), 'Vacaciones y ausencias legales creadas correctamente.');
    }

    /**
     * Check for task conflicts and save vacation/legal absence dates.
     *
     * This method is called via AJAX before storing absences.
     * - If there are conflicts with tasks, it returns a JSON response with the conflict info.
     * - If there are no conflicts, it deletes tasks on selected dates and saves absences.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function checkAndSave(Request $request, User $user): JsonResponse
    {
        $vacationDates = $request->input('vacation_dates', []);
        $legalDates = $request->input('legal_dates', []);
        $allDates = array_merge($vacationDates, $legalDates);

        $confirmDeleteTasks = $request->input('confirm_delete_tasks', false);

        //Check task conflicts only if there is NO confirmation
        if (!$confirmDeleteTasks) {
            $conflicts = $this->calendarService->checkTaskConflicts($user, $allDates, $vacationDates, $legalDates);
            if (!empty($conflicts)) {
                return response()->json([
                    'status' => 'conflicts',
                    'conflicts' => $conflicts,
                ]);
            }
        }

        //If there is confirmation or no conflicts, delete tasks
        if ($confirmDeleteTasks) {
            $this->calendarService->deleteTasksForDates($user, $allDates);
        }

        // Save user absences
        $this->calendarService->saveUserAbsences($user, CalendarType::VACATION->value, $vacationDates);
        $this->calendarService->saveUserAbsences($user, CalendarType::LEGAL_ABSENCE->value, $legalDates);

        return response()->json([
            'status' => 'success',
            'message' => 'Vacaciones y ausencias legales creadas correctamente.',
            'redirect' => route('admin.users.index',  ['type' => UserTypeEnum::MOBILE->value]),
        ]);
    }
}
