<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\Subtask;
use App\Models\RecurrentTask;
use Illuminate\Support\Carbon;
use App\Http\Requests\Admin\StoreOrUpdateTaskRequest;
use Illuminate\Http\UploadedFile;

class TaskController extends WebController
{
    /**
     * Display a paginated list of users with optional filters.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return $this->tryCatch(function () use ($request) {
            $query = Task::with(['user', 'subtasks', 'recurrentTask'])
                ->when($request->filled('user'), function ($q) use ($request) {
                    $q->whereHas('user', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%' . $request->user . '%')
                            ->orWhere('surname', 'like', '%' . $request->user . '%');
                    });
                })
                ->when($request->filled('title'), fn($q) => $q->where('title', 'like', '%' . $request->title . '%'))
                ->when($request->filled('recurrent'), function ($q) use ($request) {
                    if ($request->recurrent === 'yes') {
                        $q->whereNotNull('recurrent_task_id');
                    } elseif ($request->recurrent === 'no') {
                        $q->whereNull('recurrent_task_id');
                    }
                })
                ->orderByDesc('created_at')
                ->paginate(10);

            return view('web.admin.tasks.index', [
                'query' => $query,
                'request' => $request
            ]);
        });
    }
    /**
     * Show the form to create a new task.
     *
     * @param int|null $userId
     * @return View|RedirectResponse
     */
    public function create(int $userId = null): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($userId) {
            $user = User::findOrFail($userId);
            $existingTasks = Task::with('user')->with('subtasks')->latest()->get();
            return view('web.admin.tasks.create', compact('user', 'existingTasks'));
        }, route('admin.users.index'));
    }

    /**
     * Store a new task and its subtasks in database.
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

            return redirect()->route('admin.users.tasks', $userId);
        }, route('admin.users.tasks', $userId), 'Tarea creada correctamente');
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
     * @param int $taskId
     * @return View|RedirectResponse
     */
    public function edit(int $taskId): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($taskId) {
            $task = Task::with('recurrentTask')->findOrFail($taskId);
            $subtasksArray = $task->subtasks->map(fn($st) => [
                'id' => $st->id,
                'title' => $st->title,
                'description' => $st->description,
                'note' => $st->note,
                'order' => $st->order,
                'status' => $st->status,
                'pictogram_path' => $st->pictogram_path,
            ])->toArray();

            return view('web.admin.tasks.edit', [
                'task' => $task,
                'subtasksArray' => $subtasksArray,
                'recurrentTask' => $task->recurrentTask,
            ]);
        }, route('admin.users.index'));

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

            return redirect()->route('admin.users.tasks', $userId);
        }, route('admin.tasks.edit', ['id' => $task->id]), 'Tarea actualizada con éxito.');
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
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'order' => $data['order'] ?? 0,
            'status' => $data['status'] ?? 'pending',
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
                // Datos de recurrente
                'is_recurrent' => (bool) $task->recurrentTask,
                'days_of_week' => $task->recurrentTask?->days_of_week ?? [],
                'recurrent_start_date' => optional($task->recurrentTask)->start_date,
                'recurrent_end_date' => optional($task->recurrentTask)->end_date,
                // Subtareas
                'subtasks' => $task->subtasks
            ]
        ]);
    }
}
