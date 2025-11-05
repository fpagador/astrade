<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreOrUpdateTaskRequest;
use App\Http\Requests\Admin\UserTaskFilterRequest;
use App\Models\User;
use App\Models\Task;
use App\Services\UserTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Repositories\TaskRepository;
use Illuminate\Support\Carbon;

class UserTaskController extends WebController
{

    /**
     * Constructor
     *
     * @param UserTaskService $userTaskService
     * @param TaskRepository $taskRepository
     */
    public function __construct(
        protected UserTaskService $userTaskService,
        protected TaskRepository $taskRepository
    ) {}

    /**
     * Display a list of tasks assigned to the specified user,
     * with optional filtering by scheduled date and status.
     *
     * @param User $user
     * @param UserTaskFilterRequest  $request
     * @return View|RedirectResponse
     */
    public function index(User $user, UserTaskFilterRequest $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($user, $request) {
            $date = $request->get('date', now()->toDateString());
            $filters = $request->only(['title', 'status']);
            $viewMode = $request->get('viewMode', 'weekly');
            $backUrl = $request->get('back_url');

            // Service returns all required pieces for the view
            $calendarData = $this->userTaskService->getUserTasksForCalendar(
                $user->id,
                $date,
                $filters
            );

            // Extract variables
            $tasksByDate    = $calendarData['tasksByDate'];
            $tasksDaily     = $calendarData['tasksDaily'];
            $timeCounts     = $calendarData['timeCounts'];
            $hasAnyTasks    = $calendarData['hasAnyTasks'];
            $specialDays    = $calendarData['specialDays'];
            $calendarColors = $calendarData['calendarColors'];

            $allUsers = $this->userTaskService->getAssignableUsers();

            return view('web.admin.users.tasks', compact(
                'user',
                'date',
                'tasksByDate',
                'tasksDaily',
                'timeCounts',
                'hasAnyTasks',
                'backUrl',
                'specialDays',
                'viewMode',
                'calendarColors',
                'allUsers',
                'calendarData'
            ));
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

    /**
     * Show the form to create a new task.
     * @param Request $request
     * @param User $user
     * @return View|RedirectResponse
     */
    public function create(Request $request, User $user): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $user) {
            $existingTasks = $this->userTaskService->formatExistingTasks($user);

            $taskToClone = null;
            $cloneId = $request->query('clone');

            return view('web.admin.tasks.create', [
                'user' => $user,
                'existingTasks' => $existingTasks,
                'colors' => $this->userTaskService->getColors(),
                'date' => $request->query('date', now()->toDateString()),
                'viewMode' => $request->get('viewMode', 'weekly'),
                'weekDays' => $this->userTaskService->weekDays,
                'oldSubtasks' => old('subtasks', []),
                'taskToClone' => $taskToClone,
                'cloneId' => $cloneId
            ]);
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

    /**
     * Store a new task and its subtasks in database.block font-medium mb-1 flex items-center gap-1
     *
     * @param StoreOrUpdateTaskRequest  $request
     * @param User $user
     * @return RedirectResponse
     */
    public function store(StoreOrUpdateTaskRequest $request, User $user): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $user) {
            $data = $request->validated();
            $data['pictogram_path'] = $this->userTaskService->processPictogram(
                $request->file('pictogram_path') ?? $request->input('pictogram_path'),
                $request->input('pictogram_name') ?? null
            );

            $subtasks = $data['subtasks'] ?? [];
            foreach ($subtasks as $i => &$st) {
                $base64   = $request->input("subtasks.$i.pictogram_base64");
                $filename = $request->input("subtasks.$i.pictogram_name");
                $path     = $request->input("subtasks.$i.pictogram_path");

                if ($base64 && $filename) {
                    $st['pictogram_path'] = $this->userTaskService->processPictogram($base64, $filename);
                } elseif ($path) {
                    $internalPath = str_replace('/storage/', '', $path);
                    $st['pictogram_path'] = $this->userTaskService->processPictogram($internalPath, null);
                } else {
                    $st['pictogram_path'] = null;
                }
            }
            $data['subtasks'] = $subtasks;

            // Validate subtasks presence
            if (!$this->userTaskService->hasValidSubtasksArray($data['subtasks'] ?? [])) {
                return back()
                    ->withErrors(['general' => 'Debes añadir al menos una subtarea.'])
                    ->withInput()
                    ->with('oldSubtasks', $data['subtasks'] ?? []);
            }

            // Delegate to service
            $this->userTaskService->createTask($user->id, $data);

            return redirect()->route('admin.users.tasks', [
                'user' => $user,
                'date' => $request->input('date') ?? now()->toDateString(),
                'viewMode' => $request->input('viewMode', 'weekly')
            ]);
        }, route('admin.users.tasks', $user), 'Tarea creada correctamente');
    }

    /**
     * Delete one or multiple tasks (and their subtasks) for the given user.
     * If no task is selected via checkbox, the task ID from the route is used.
     *
     * @param Request $request
     * @param int $userId
     * @param int|null $taskId
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, int $userId, int $taskId = null): JsonResponse|RedirectResponse
    {
        $deleteSeries = $request->input('deleteSeries', false);
        $message = $deleteSeries
            ? 'Las tareas recurrentes se han eliminado correctamente.'
            : 'La tarea se ha eliminado correctamente.';

        return $this->tryCatch(function () use ($request, $userId, $taskId, $deleteSeries, $message) {
            $this->userTaskService->deleteTask($userId, $taskId, $deleteSeries);

            return response()->json([
                'message' => $message,
                'redirect_url' => route('admin.users.tasks', [
                    'user' => $userId,
                    'date' => $request->input('date') ?? now()->toDateString()
                ])
            ]);
        }, route('admin.users.tasks', ['user' => $userId]), $message);
    }

    /**
     * Show the form to edit a task.
     *
     * @param Request $request
     * @param int $taskId
     * @return View|RedirectResponse
     */
    public function edit(Request $request, int $taskId): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $taskId) {
            $task = $this->taskRepository->findWithRelations($taskId, ['recurrentTask', 'subtasks']);
            $subtasksArray = $this->taskRepository->getSubtasksArray($task);
            $selectedDays = $this->userTaskService->getSelectedDays($task->recurrentTask?->days_of_week);
            $editSeries = (int) $request->query('edit_series', 0);

            return view('web.admin.tasks.edit', [
                'task' => $task,
                'subtasksArray' => $subtasksArray,
                'recurrentTask' => $task->recurrentTask,
                'colors' => $this->userTaskService->getColors(),
                'date' => $request->query('date', now()->toDateString()),
                'viewMode' => $request->get('viewMode', 'weekly'),
                'weekDays' => $this->userTaskService->weekDays,
                'selectedDays' => $selectedDays,
                'editSeries' => $editSeries,
                'disableFields' => $task->recurrentTask && $editSeries === 0,
            ]);
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));

    }

    /**
     * Update an existing task and its subtasks.
     *
     * @param StoreOrUpdateTaskRequest $request
     * @param Task $task
     * @return RedirectResponse
     */
    public function update(StoreOrUpdateTaskRequest $request, Task $task): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $task) {
            $data = $request->validated();
            $editSeries = $request->input('edit_series', 0);

            if ($request->hasFile('pictogram_path')) {
                $data['pictogram_path'] = $request->file('pictogram_path');
            }

            // attach uploaded subtask files (supporting either naming convention)
            $formSubtasks = $request->input('subtasks', []);
            foreach ($formSubtasks as $index => &$subtask) {
                $fileKey = $subtask['id'] ?? 'new_' . $index;
                $base64 = $request->input("subtasks.$index.pictogram_base64");
                $filename = $request->input("subtasks.$index.pictogram_name");

                if ($request->hasFile("subtask_pictograms.$fileKey")) {
                    $subtask['pictogram'] = $request->file("subtask_pictograms.$fileKey");
                } elseif ($base64 && $filename) {
                    $subtask['pictogram'] = $this->userTaskService->storeBase64Pictogram($base64, $filename);
                }
            }
            $data['subtasks'] = $formSubtasks;

            $this->userTaskService->updateTask($task, $data, $editSeries == true);

            return redirect()->route('admin.users.tasks', [
                'user' => $task->user_id,
                'date' => $request->input('date') ?? now()->toDateString(),
                'viewMode' => $request->input('viewMode', 'weekly')
            ]);
        }, route('admin.users.tasks.edit', ['id' => $task->id]), 'Tarea actualizada con éxito.');
    }

    /**
     * Return the given task and its relationships (subtasks and recurrence)
     * as a JSON response formatted for frontend consumption.
     *
     * @param Task $task
     * @return JsonResponse
     */
    public function json(Task $task): JsonResponse
    {
        return response()->json($this->userTaskService->getTaskJson($task));
    }

    /**
     * Returns the HTML fragment with the task details (including actions and subtasks).
     *
     * @param Task $task
     * @param Request $request
     * @return View
     */
    public function taskDetail(Task $task, Request $request): View
    {
        return $this->tryCatch(function () use ($task, $request) {
            $data = $this->userTaskService->getTaskDetailData($task, $request->input('date', null));
            return view('web.admin.users.partials.task-detail', $data);
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

    /**
     * Returns the daily tasks for a user with optional filters (title and status).
     * Provides an HTML fragment if the request is AJAX, otherwise returns JSON.
     *
     * @param User $user
     * @param Request $request
     *
     * @return JsonResponse|string
     */
    public function daily(User $user, Request $request): JsonResponse|string
    {
        try {
            $date = $request->query('date', now()->toDateString());
            $titleFilter = $request->query('title', '');
            $statusFilter = $request->query('status', '');
            $filters = ['title' => $titleFilter, 'status' => $statusFilter];

            $tasks = $this->userTaskService->getDailyTasks($user->id, $date, $filters, 10);

            // Count tasks per hour
            $timeCounts = [];
            foreach ($tasks as $task) {
                if ($task->scheduled_time) {
                    $time = $task->scheduled_time instanceof Carbon
                        ? $task->scheduled_time
                        : Carbon::parse($task->scheduled_time);

                    $timeKey = $time->format('H:i');
                    $timeCounts[$timeKey] = ($timeCounts[$timeKey] ?? 0) + 1;
                }
            }

            $hasAnyTasks = $user->tasks()->exists();

            if ($request->ajax()) {
                return view('web.admin.users.partials.daily-tasks', compact(
                    'tasks',
                    'user',
                    'date',
                    'timeCounts',
                    'hasAnyTasks'
                ))->render();
            }

            return response()->json(['error' => 'Invalid request'], 400);

        } catch (\Throwable $e) {
            // Log the error for debugging
            Log::record('error', $user->id . " " . $e->getTraceAsString(), [
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while retrieving tasks.',
                'message' => '',
            ], 500);
        }
    }

    /**
     * Check if a user has a scheduling conflict.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function checkConflict(int $userId, Request $request): JsonResponse
    {
        $date = $request->query('scheduled_date');
        $time = $request->query('scheduled_time');

        $conflict = $this->userTaskService->hasConflict($userId, $date, $time);

        return response()->json(['conflict' => $conflict]);
    }

    /**
     * Check if a given date is a non-working day for the user.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function checkNonWorking(int $userId, Request $request): JsonResponse
    {
        $date = $request->query('scheduled_date');

        $nonWorking = $this->userTaskService->isNonWorkingDay($userId, $date);
        $festiveDay = $this->userTaskService->isFestiveDay($userId, $date);

        return response()->json(['nonWorking' => $nonWorking, 'festiveDay' => $festiveDay]);
    }

    /**
     * Checks if a date range of recurring tasks has absence days and festives days.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function checkNonWorkingRange(int $userId, Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $templateId = $this->userTaskService->getTemplateId($userId);
        $festiveDays = $templateId ? $this->userTaskService->isFestiveDays($templateId) : [];

        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($current->lte($end)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        $nonWorkingDates = [];
        $festiveDates = [];
        foreach ($dates as $date) {
            if ($this->userTaskService->isNonWorkingDay($userId, $date)) {
                $nonWorkingDates[] = $date;
            }
            if (in_array($date, $festiveDays)) {
                $festiveDates[] = $date;
            }
        }

        return response()->json(['nonWorkingDates' => $nonWorkingDates, 'festiveDates' => $festiveDates]);
    }
}
