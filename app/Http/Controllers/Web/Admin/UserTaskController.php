<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreOrUpdateTaskRequest;
use App\Http\Requests\Admin\UserTaskFilterRequest;
use App\Models\RecurrentTask;
use App\Models\Subtask;
use App\Models\User;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class UserTaskController extends WebController
{
    /**
     * Display a list of tasks assigned to the specified user,
     * with optional filtering by scheduled date and status.
     *
     * @param int $id
     * @param UserTaskFilterRequest  $request
     * @return View|RedirectResponse
     */
    public function index(int $id, UserTaskFilterRequest $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($id, $request) {
            $user = User::findOrFail($id);

            // Get the date to display (from the filter or today by default)
            $date = $request->filled('date')
                ? \Carbon\Carbon::parse($request->date)->format('Y-m-d')
                : \Carbon\Carbon::today()->format('Y-m-d');

            // Base query with subtasks eager loaded
            $query = Task::with('subtasks')->where('user_id', $id);

            // Apply filter by required date (selected or today)
            $query->whereDate('scheduled_date', $date);

            // Check if user has any task at all (used for empty states)
            $hasAnyTasks = $query->exists();

            // Apply filters
            $request->validated();

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('scheduled_date')) {
                $scheduledDate = Carbon::parse($request->scheduled_date)->format('Y-m-d');
                $query->whereDate('scheduled_date', $scheduledDate);
            } else {
                // Apply the base filter with $date to show tasks from the selected day
                $query->whereDate('scheduled_date', $date);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $tasks = $query->orderBy('scheduled_date')->orderBy('scheduled_time')->paginate(15);

            $timeCounts = [];
            foreach ($tasks as $task) {
                $key = $task->scheduled_time;

                if (is_object($key)) {
                    $key = $key->format('H:i');
                }

                $key = $key ?? 'no_hora';

                $timeCounts[$key] = ($timeCounts[$key] ?? 0) + 1;
            }

            return view('web.admin.users.tasks', compact(
                'user',
                'tasks',
                'hasAnyTasks',
                'date',
                'timeCounts'
                )
            );
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

    /**
     * Show the form to create a new task.
     * @param Request $request
     * @param int|null $userId
     * @return View|RedirectResponse
     */
    public function create(Request $request, int $userId): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $userId) {
            $user = User::findOrFail($userId);
            $existingTasks = Task::with('user')->with('subtasks')->latest()->get();
            $colors = Task::getColors();
            $date = $request->query('date', now()->toDateString());
            return view('web.admin.tasks.create', compact('user', 'existingTasks', 'colors', 'date'));
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

    /**
     * Store a new task and its subtasks in database.block font-medium mb-1 flex items-center gap-1
     *
     * @param StoreOrUpdateTaskRequest  $request
     * @param int $userId
     * @return RedirectResponse
     */
    public function store(StoreOrUpdateTaskRequest $request, int $userId): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $userId) {
            $data = $request->validated();

            if (!$this->hasValidSubtasks($request)) {
                return back()->withErrors(['general' => 'Debes añadir al menos una subtarea.'])->withInput();
            }

            $data['is_recurrent'] = $request->boolean('is_recurrent');
            $data['days_of_week'] = $request->input('days_of_week', []);

            $recurrentId = null;

            if ($data['is_recurrent']) {
                $recurrentId = $this->handleRecurrentTask($data, $userId);
            }

            $this->createTask($data, $userId, $recurrentId);

            return redirect()->route('admin.users.tasks',[
                'id' => $userId,
                'date' => $request->input('date') ?? now()->toDateString()
            ]);
        }, route('admin.users.tasks', $userId), 'Tarea creada correctamente');
    }

    /**
     * Delete one or multiple tasks (and their subtasks) for the given user.
     * If no task is selected via checkbox, the task ID from the route is used.
     *
     * @param Request $request
     * @param int $userId
     * @param int|null $taskId
     * @return RedirectResponse
     */
    public function destroy(Request $request, int $userId, int $taskId = null): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $userId, $taskId) {
            $taskIds = $request->input('selected_tasks', []);

            // If no array is provided, use the unique ID of the route
            if (empty($taskIds) && $taskId) {
                $taskIds = [$taskId];
            }

            if (empty($taskIds)) {
                return redirect()->route('admin.users.tasks', $userId)
                    ->with('general', 'No se seleccionaron tareas para eliminar.');
            }

            $tasks = Task::with('subtasks')->whereIn('id', $taskIds)->get();

            foreach ($tasks as $task) {
                foreach ($task->subtasks as $subtask) {
                    if ($subtask->pictogram_path && Storage::disk('public')->exists($subtask->pictogram_path)) {
                        Storage::disk('public')->delete($subtask->pictogram_path);
                    }
                }

                if ($task->pictogram_path && Storage::disk('public')->exists($task->pictogram_path)) {
                    Storage::disk('public')->delete($task->pictogram_path);
                }

                $task->delete();
            }

            return redirect()->route('admin.users.tasks', [
                'id' => $userId,
                'date' => $request->input('date') ?? now()->toDateString()
            ]);
        }, route('admin.users.tasks', $userId), 'Tarea eliminada correctamente.');
    }

    /**
     * Normalize Spanish weekday names to English.
     *
     * @param array $days
     * @return array
     */
    private function normalizeDays(array $days): array
    {
        $map = [
            'lunes' => 'monday',
            'martes' => 'tuesday',
            'miercoles' => 'wednesday',
            'miércoles' => 'wednesday',
            'jueves' => 'thursday',
            'viernes' => 'friday',
            'sabado' => 'saturday',
            'sábado' => 'saturday',
            'domingo' => 'sunday',
        ];

        return array_unique(array_filter(array_map(function ($day) use ($map) {
            $key = strtolower(str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $day));
            return $map[$key] ?? null;
        }, $days)));
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
            $task = Task::with('recurrentTask')->findOrFail($taskId);
            $subtasksArray = $task->subtasks()
                ->orderBy('order')
                ->get()
                ->map(fn($st) => [
                    'id' => $st->id,
                    'title' => $st->title,
                    'description' => $st->description,
                    'note' => $st->note,
                    'pictogram_path' => $st->pictogram_path,
                    'status' => $st->status
            ])->toArray();

            $colors = Task::getColors();
            $date = $request->query('date', now()->toDateString());
            return view('web.admin.tasks.edit', [
                'task' => $task,
                'subtasksArray' => $subtasksArray,
                'recurrentTask' => $task->recurrentTask,
                'colors' => $colors,
                'date' => $date
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
            $userId = $task->user_id ?? $request->input('user_id');
            if (!$userId) {
                return redirect()->route('admin.users.index')->with('error', 'No se pudo determinar el usuario.');
            }

            $data = $request->validated();
            $data['is_recurrent'] = $request->boolean('is_recurrent');
            $data['days_of_week'] = $request->input('days_of_week', []);

            $this->handleRecurrentTaskUpdate($task, $data, $userId);

            if ($request->hasFile('pictogram')) {
                $data['pictogram_path'] = $this->handlePictogramUpload($request->file('pictogram'), $task->pictogram_path);
            }

            $task->update($data);
            $this->syncSubtasks($task, $request);

            return redirect()->route('admin.users.tasks',[
                'id' => $userId,
                'date' => $request->input('date') ?? now()->toDateString()
            ]);
        }, route('admin.users.tasks.edit', ['id' => $task->id]), 'Tarea actualizada con éxito.');
    }

    /**
     * Create a new task and its subtasks.
     *
     * @param array $data
     * @param int $userId
     * @param ?int $recurrentId
     * @return Task
     */
    private function createTask(array $data, int $userId, ?int $recurrentId = null): Task
    {
        $task = Task::create([
            'user_id' => $userId,
            'assigned_by' => auth()->id(),
            'title' => $data['title'],
            'color' => $data['color'],
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'order' => $data['order'] ?? 0,
            'status' => $subtask['status'] ?? 'pending',
            'recurrent_task_id' => $recurrentId,
        ]);

        if (request()->hasFile('pictogram')) {
            $task->pictogram_path = $this->handlePictogramUpload(request()->file('pictogram'));
            $task->save();
        }

        $this->createSubtasks($task, $data['subtasks'] ?? []);
        return $task;
    }

    /**
     * Create and associate subtasks to a task.
     *
     * @param Task $task
     * @param array $subtasks
     * @return void
     */
    private function createSubtasks(Task $task, array $subtasks): void
    {
        foreach ($subtasks as $index => $subtask) {
            $model = new Subtask([
                'title' => $subtask['title'],
                'description' => $subtask['description'] ?? null,
                'note' => $subtask['note'] ?? null,
                'order' => $subtask['order'] ?? 0,
                'status' => $subtask['status'] ?? 'pending',
            ]);

            if (request()->hasFile("subtask_files.$index")) {
                $file = request()->file("subtask_files.$index");
                $model->pictogram_path = $this->handlePictogramUpload($file);
            }

            $task->subtasks()->save($model);
        }
    }

    /**
     * Synchronize existing subtasks with form data.
     * Creates new, updates existing, and deletes removed subtasks.
     *
     * @param Task $task
     * @param StoreOrUpdateTaskRequest $request
     * @return void
     */
    private function syncSubtasks(Task $task, StoreOrUpdateTaskRequest $request): void
    {
        $formSubtasks = $request->input('subtasks', []);
        $submittedIds = collect($formSubtasks)->pluck('id')->filter()->all();

        foreach ($formSubtasks as $index => $subtaskData) {
            $id = $subtaskData['id'] ?? null;
            $fileKey = $id ?? 'new_' . $index;

            $values = [
                'title' => $subtaskData['title'],
                'description' => $subtaskData['description'] ?? '',
                'note' => $subtaskData['note'] ?? '',
                'order' => $subtaskData['order'] ?? 0,
                'status' => $subtaskData['status'] ?? null,
            ];

            if ($request->hasFile("subtask_pictograms.$fileKey")) {
                $file = $request->file("subtask_pictograms.$fileKey");
                $subtask = $id ? $task->subtasks->firstWhere('id', $id) : null;
                $oldPath = $subtask?->pictogram_path;
                $values['pictogram_path'] = $this->handlePictogramUpload($file, $oldPath);
            }

            if ($id && ($subtask = $task->subtasks->firstWhere('id', $id))) {
                $subtask->update($values);
            } else {
                $task->subtasks()->create($values);
            }
        }

        $task->subtasks()->whereNotIn('id', $submittedIds)->get()->each(function ($subtask) {
            if ($subtask->pictogram_path && Storage::disk('public')->exists($subtask->pictogram_path)) {
                Storage::disk('public')->delete($subtask->pictogram_path);
            }
            $subtask->delete();
        });
    }

    /**
     * Upload and store a pictogram, optionally deleting the old one.
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @return string
     */
    private function handlePictogramUpload(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        return $file->store('pictograms', 'public');
    }

    /**
     * Handle recurrent task creation logic.
     *
     * @param array $data
     * @param int $userId
     * @return int|null
     */
    private function handleRecurrentTask(array &$data, int $userId): ?int
    {
        $recurrent = RecurrentTask::create([
            'user_id' => $userId,
            'start_date' => $data['recurrent_start_date'],
            'end_date' => $data['recurrent_end_date'],
            'days_of_week' => $data['days_of_week']
        ]);

        $start = Carbon::parse($data['recurrent_start_date']);
        $normalizedDays = $this->normalizeDays($data['days_of_week']);

        foreach ($normalizedDays as $day) {
            if (strtolower($start->format('l')) === $day) {
                $data['scheduled_date'] = $start->toDateString();
                break;
            }
        }

        return $recurrent->id;
    }

    /**
     * Update or delete the recurrent task based on form input.
     *
     * @param Task $task
     * @param array $data
     * @param int $userId
     * @return void
     */
    private function handleRecurrentTaskUpdate(Task $task, array &$data, int $userId): void
    {
        if (!$data['is_recurrent']) {
            $data['days_of_week'] = null;
            $data['recurrent_start_date'] = null;
            $data['recurrent_end_date'] = null;

            if ($task->recurrent_task_id) {
                $task->recurrentTask()->delete();
                $data['recurrent_task_id'] = null;
            }
        } else {
            if ($task->recurrent_task_id) {
                $task->recurrentTask()->update([
                    'days_of_week' => $data['days_of_week'],
                    'start_date' => $data['recurrent_start_date'],
                    'end_date' => $data['recurrent_end_date'],
                ]);
            } else {
                $recurrent = RecurrentTask::create([
                    'user_id' => $userId,
                    'days_of_week' => $data['days_of_week'],
                    'start_date' => $data['recurrent_start_date'],
                    'end_date' => $data['recurrent_end_date'],
                ]);
                $data['recurrent_task_id'] = $recurrent->id;
            }
        }
    }

    /**
     * Validate that at least one subtask has a non-empty title.
     *
     * @param StoreOrUpdateTaskRequest $request
     * @return bool
     */
    private function hasValidSubtasks(StoreOrUpdateTaskRequest $request): bool
    {
        return $request->has('subtasks') && !empty(array_filter($request->subtasks, fn ($s) => !empty($s['title'])));
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
        $task = $task->load(['subtasks', 'recurrentTask']);

        return response()->json([
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'scheduled_date' => $task->scheduled_date,
                'scheduled_time' => $task->scheduled_time,
                'estimated_duration_minutes' => $task->estimated_duration_minutes,
                'order' => $task->order,
                'status' => $task->status,
                'pictogram_path' => $task->pictogram_path,

                //Recurring data
                'is_recurrent' => (bool) $task->recurrentTask,
                'days_of_week' => $task->recurrentTask?->days_of_week ?? [],
                'recurrent_start_date' => optional($task->recurrentTask)->start_date,
                'recurrent_end_date' => optional($task->recurrentTask)->end_date,

                // Subtasks
                'subtasks' => $task->subtasks
            ]
        ]);
    }

    /**
     * Outlook-style calendar with mini calendar and daily columns.
     * Clicking on a task loads the details below.
     *
     * @param int $userId
     * @param Request $request
     * @return View
     */
    public function calendar(int $userId, Request $request): View
    {
        return $this->tryCatch(function () use ($userId, $request) {
            $user = User::findOrFail($userId);
            $date = $request->input('date', now()->toDateString());

            // Get user tasks
            $tasks = Task::where('user_id', $userId)
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            // Group tasks by date and prepare a simple array for Alpine
            $tasksByDate = [];
            foreach ($tasks as $task) {
                $dateKey = $task->scheduled_date instanceof \Carbon\Carbon
                    ? $task->scheduled_date->format('Y-m-d')
                    : $task->scheduled_date;

                $tasksByDate[$dateKey][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'scheduled_time' => $task->scheduled_time ? $task->scheduled_time->format('H:i') : null,
                    'color' => $task->color,
                ];
            }

            return view('web.admin.users.tasks-calendar', compact('user', 'tasksByDate', 'date'));
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
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
            $task->load('subtasks');
            $user = $task->user;
            $date = $request->input('date', optional($task->scheduled_date)->format('Y-m-d') ?? now()->toDateString());

            // Get all user tasks for date
            $tasksForDay = Task::where('user_id', $user->id)
                ->whereDate('scheduled_date', $date)
                ->get();

            //Count tasks per hour
            $timeCounts = $tasksForDay->groupBy(function ($t) {
                return $t->scheduled_time instanceof \Carbon\Carbon
                    ? $t->scheduled_time->format('H:i')
                    : ($t->scheduled_time ? Carbon::parse($t->scheduled_time)->format('H:i') : 'no_hora');
            })->map(fn($group) => $group->count())->toArray();

            return view('web.admin.users.partials.task-detail', compact('task','user','date', 'timeCounts'));
        }, route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]));
    }

}
