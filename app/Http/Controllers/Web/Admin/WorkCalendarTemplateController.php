<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\WorkCalendarTemplateRequest;
use App\Models\WorkCalendarTemplate;
use App\Models\WorkCalendarDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use App\Enums\CalendarStatus;

class WorkCalendarTemplateController extends WebController
{
    /**
     * Display a list of all calendar templates.
     *
     * @param Request $request
     * @return View | RedirectResponse
     */
    public function index(Request $request): View | RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $query = WorkCalendarTemplate::withCount([
                'days as holidays_count' => function ($q) {
                    $q->where('day_type', 'holiday');
                }
            ]);

            if ($request->filled('name')) {
                $query->where('name', 'like', "%{$request->name}%");
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $sort = $request->get('sort', 'name');
            $direction = $request->get('direction', 'asc');

            // default columns
            $sortableColumns = ['name', 'year', 'status'];

            if (in_array($sort, $sortableColumns)) {
                $query->orderBy("work_calendar_templates.$sort", $direction);
            }

            $templates = $query->paginate(15)->withQueryString();
            $statuses = collect(CalendarStatus::cases())
                ->mapWithKeys(fn($case) => [$case->value => CalendarStatus::label($case)]);

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
        $statusOptions = collect(CalendarStatus::cases())
            ->mapWithKeys(fn($case) => [$case->value => CalendarStatus::label($case)])
            ->toArray();
        $existingCalendars = WorkCalendarTemplate::all();
        return view('web.admin.calendars.create', compact('statusOptions','existingCalendars'));
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
            $template = WorkCalendarTemplate::create($data);

            //Generate weekends and holidays for the staff
            $this->generateCalendarDays($template, $request->holidays_json);

            return redirect()->route('admin.calendars.index', $template);
        }, route('admin.calendars.index'), 'Plantilla creada correctamente.');
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
            $template->load('days');
            $holidaysJson = $template->days
                ->where('day_type', 'holiday')
                ->pluck('date')
                ->values()
                ->toJson();

            $statusOptions = collect(CalendarStatus::cases())
                ->filter(fn($case) =>
                    $template->status === CalendarStatus::DRAFT->value || $case !== CalendarStatus::DRAFT
                )
                ->mapWithKeys(fn($case) => [$case->value => CalendarStatus::label($case)])
                ->toArray();

            return view('web.admin.calendars.edit', compact('template', 'holidaysJson', 'statusOptions'));
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
            $data = $request->validated();
            $template->update($data);

            //Delete existing days of the year
            WorkCalendarDay::where('template_id', $template->id)
                ->whereYear('date', $template->year)
                ->delete();

            //Generate weekends and holidays for the staff
            $this->generateCalendarDays($template, $request->holidays_json);

            return redirect()->route('admin.calendars.index', $template);
        }, route('admin.calendars.index'), 'Plantilla actualizada correctamente.');
    }

    /**
     * Generate weekends and holidays for a work calendar template.
     *
     * This function populates the `work_calendar_days` table with all weekends
     * and additional holidays for the given template and year.
     *
     * @param  WorkCalendarTemplate  $template  The work calendar template to populate.
     * @param  string|null           $holidaysJson  JSON string containing additional holiday dates (YYYY-MM-DD).
     * @return void
     */
    private function generateCalendarDays(WorkCalendarTemplate $template, ?string $holidaysJson): void
    {
        // Start and end dates for the given year
        $start = Carbon::create($template->year, 1, 1);
        $end = Carbon::create($template->year, 12, 31);

        $days = [];

        // Generate all weekends
        for ($date = $start; $date->lte($end); $date->addDay()) {
            if ($date->isWeekend()) {
                $days[] = [
                    'template_id' => $template->id,
                    'date' => $date->toDateString(),
                    'day_type' => 'weekend',
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $holidays = json_decode($holidaysJson, true) ?? [];

        // Add holidays
        foreach ($holidays as $holiday) {
            $days[] = [
                'template_id' => $template->id,
                'date' => $holiday,
                'day_type' => 'holiday',
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert all days
        if (!empty($days)) {
            WorkCalendarDay::insert($days);
        }
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
            if ($template->users()->count() > 0) {
                return back()->withErrors(['general' => 'Cannot delete, template assigned to users.']);
            }

            $template->delete();
            return redirect()->route('admin.calendars.index');
        }, route('admin.calendars.index'), 'Template deleted successfully.');
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
            $data = $request->validate([
                'date' => 'required|date',
                'day_type' => 'required|in:holiday,weekend,workday',
                'description' => 'nullable|string|max:255',
            ]);

            $template->days()->create($data);

            return back();
        }, route('admin.calendars.edit', $template), 'Day added successfully.');
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
            $day->delete();
            return back();
        }, route('admin.calendars.edit', $template), 'Day removed successfully.');
    }

    /**
     * Get template to clone
     *
     * @param  WorkCalendarTemplate  $template
     * @return JsonResponse
     */
    public function cloneTemplateData(WorkCalendarTemplate $template): JsonResponse
    {
        $template->load('days');

        return response()->json([
            'name' => $template->name,
            'status' => $template->status,
            'holidays' => $template->days
                ->where('day_type', 'holiday')
                ->pluck('date')
                ->values()
        ]);
    }

}
