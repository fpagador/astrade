<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\WorkCalendarTemplateRequest;
use App\Models\WorkCalendarTemplate;
use App\Models\WorkCalendarDay;
use App\Repositories\CompanyRepository;
use App\Repositories\UserRepository;
use App\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkCalendarTemplateController extends WebController
{

    /**
     * Construct
     *
     * @param CalendarService $calendarService
     * @param CompanyRepository $companyRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        protected CalendarService $calendarService,
        protected CompanyRepository $companyRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Display a list of all calendar templates.
     *
     * @param Request $request
     * @return View | RedirectResponse
     */
    public function index(Request $request): View | RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $filters = $request->only(['name', 'year', 'status']);
            $templates = $this->calendarService->getPaginatedTemplates(
                $filters,
                $request->get('sort', 'name'),
                $request->get('direction', 'asc')
            );

            $statuses = $this->calendarService->getStatusOptions();

            return view('web.admin.calendars.index', compact('templates', 'statuses'));
        }, route('admin.calendars.index'));
    }

    /**
     * Show the form for creating a new calendar template.
     *
     * @return View
     */
    public function create(): View
    {
        $statusOptions = $this->calendarService->getStatusOptions();
        $cloneableCalendars = $this->calendarService->getActiveTemplates();
        $companies = $this->companyRepository->getAll();
        $users = $this->userRepository->getAllUsersForCompany();
        return view('web.admin.calendars.create', compact(
            'statusOptions',
            'cloneableCalendars',
            'companies',
            'users'
        ));
    }

    /**
     * Store a newly created calendar template in storage.
     *
     * @param  WorkCalendarTemplateRequest  $request
     * @return RedirectResponse
     */
    public function store(WorkCalendarTemplateRequest $request): RedirectResponse
    {
        return $this->tryCatch(function () use ($request) {
            $data = $request->validated();

            // Create the calendar template
            $template = $this->calendarService->createTemplate($data, $request->holidays_json);

            // Assign the template to the selected users
            $assignedUsers = $data['assigned_users'] ?? [];
            if (!empty($assignedUsers)) {
                $this->userRepository->assignTemplateToUsers($assignedUsers, $template->id);
            }

            return redirect()->route('admin.calendars.index', $template);
        }, route('admin.calendars.index'), 'la plantilla ha sido creada correctamente.');
    }

    /**
     * Show the details of a single work calendar template.
     *
     * @param WorkCalendarTemplate $template
     * @return View
     */
    public function show(WorkCalendarTemplate $template): View
    {
        $editData = $this->calendarService->getTemplateForEdit($template);
        return view('web.admin.calendars.show', $editData);
    }

    /**
     * Show the form for editing a calendar template.
     *
     * @param  WorkCalendarTemplate  $template
     * @return View | RedirectResponse
     */
    public function edit(WorkCalendarTemplate $template): View | RedirectResponse
    {
        return $this->tryCatch(function () use ($template) {
            $editData = $this->calendarService->getTemplateForEdit($template);
            return view('web.admin.calendars.edit', $editData);

        }, route('admin.calendars.index'));
    }

    /**
     * Update a calendar template in storage.
     *
     * @param  WorkCalendarTemplateRequest  $request
     * @param  WorkCalendarTemplate  $template
     * @return RedirectResponse
     */
    public function update(WorkCalendarTemplateRequest $request, WorkCalendarTemplate $template): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $template) {
            $this->calendarService->updateTemplate($template, $request->validated(), $request->holidays_json);
            return redirect()->route('admin.calendars.index', $template);
        }, route('admin.calendars.index'), 'La plantilla ha sido actualizada correctamente.');
    }

    /**
     * Remove a calendar template from storage.
     *
     * @param  WorkCalendarTemplate  $template
     * @return RedirectResponse
     */
    public function destroy(WorkCalendarTemplate $template): RedirectResponse
    {
        return $this->tryCatch(function () use ($template) {
            $this->calendarService->deleteTemplate($template);
            return redirect()->route('admin.calendars.index');
        }, route('admin.calendars.index'), 'La plantilla ha sido eliminada correctamente.');
    }

    /**
     * Add a holiday or special day to a calendar template.
     *
     * @param  Request  $request
     * @param  WorkCalendarTemplate  $template
     * @return RedirectResponse
     */
    public function addDay(Request $request, WorkCalendarTemplate $template): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $template) {
            $this->calendarService->addDayToTemplate($template, $request->all());
            return back();
        }, route('admin.calendars.edit', $template), 'Día agregado correctamente.');
    }

    /**
     * Remove a holiday or special day from a calendar template.
     *
     * @param  WorkCalendarTemplate  $template
     * @param  WorkCalendarDay  $day
     * @return RedirectResponse
     */
    public function removeDay(WorkCalendarTemplate $template, WorkCalendarDay $day): RedirectResponse
    {
        return $this->tryCatch(function () use ($day) {
            $this->calendarService->removeDayFromTemplate($day);
            return back();
        }, route('admin.calendars.edit', $template), 'Día eliminado correctamente.');
    }

    /**
     * Get template to clone
     *
     * @param  WorkCalendarTemplate  $template
     * @return JsonResponse
     */
    public function cloneTemplateData(WorkCalendarTemplate $template): JsonResponse
    {
        return response()->json($this->calendarService->getTemplateCloneData($template));
    }

    /**
     * Obtain templates for the year following the selected one.
     *
     * @param  int $year
     * @return JsonResponse
     */
    public function getFutureTemplatesByYear(int $year): JsonResponse
    {
        $templates = $this->calendarService->getActiveFutureTemplates($year);
        return response()->json($templates);
    }

}
