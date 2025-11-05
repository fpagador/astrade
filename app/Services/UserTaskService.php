<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\RecurrentTaskRepository;
use App\Repositories\SubtaskRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserAbsenceRepository;
use App\Repositories\WorkCalendarDayRepository;
use App\Models\Task;
use App\Models\RecurrentTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Enums\CalendarColor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Service class responsible for handling User Task logic.
 * It abstracts business rules and repository calls.
 */
class UserTaskService
{

    /**
     * Weekdays map: English key => Spanish label.
     *
     * @var array<string, string>
     */
    public array $weekDays = [
        'monday'    => 'Lunes',
        'tuesday'   => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday'  => 'Jueves',
        'friday'    => 'Viernes',
        'saturday'  => 'Sábado',
        'sunday'    => 'Domingo',
    ];

    /**
     * UserTaskService constructor.
     *
     * @param UserRepository $userRepository
     * @param TaskRepository $taskRepository
     * @param UserAbsenceRepository $userAbsenceRepository
     * @param WorkCalendarDayRepository $workCalendarDayRepository
     * @param RecurrentTaskRepository $recurrentTaskRepository
     * @param SubtaskRepository $subtaskRepository
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected TaskRepository $taskRepository,
        protected UserAbsenceRepository $userAbsenceRepository,
        protected WorkCalendarDayRepository $workCalendarDayRepository,
        protected RecurrentTaskRepository $recurrentTaskRepository,
        protected SubtaskRepository $subtaskRepository
    ) {}

    /**
     * Return tasks (weekly + daily view) and auxiliary calendar data.
     *
     * @param int $userId
     * @param string $date
     * @param array $filters
     * @return array {
     *      @type array  $tasksByDate
     *      @type \Illuminate\Contracts\Pagination\LengthAwarePaginator $tasksDaily
     *      @type array  $timeCounts
     *      @type bool   $hasAnyTasks
     *      @type array  $specialDays
     *      @type array  $calendarColors
     * }
     */
    public function getUserTasksForCalendar(int $userId, string $date, array $filters = []): array
    {
        // WEEKLY
        $weeklyTasks = $this->taskRepository->getUserTasks($userId);
        $tasksByDate = [];
        $timeCounterByDate = [];

        foreach ($weeklyTasks as $task) {
            $dateKey = $task->scheduled_date
                ? Carbon::parse($task->scheduled_date)->toDateString()
                : 'sin_fecha';


            $timeKey = $task->scheduled_time
                ? ($task->scheduled_time instanceof Carbon
                    ? $task->scheduled_time->format('H:i')
                    : Carbon::parse((string)$task->scheduled_time)->format('H:i'))
                : 'no_hora';

            $timeCounterByDate[$dateKey][$timeKey] = ($timeCounterByDate[$dateKey][$timeKey] ?? 0) + 1;

            $tasksByDate[$dateKey][] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'scheduled_time' => $task->scheduled_time
                    ? ( $task->scheduled_time instanceof Carbon
                        ? $task->scheduled_time->format('H:i')
                        : Carbon::parse((string)$task->scheduled_time)->format('H:i') )
                    : null,
                'color' => $task->color,
                'recurrent_task_id' => $task->recurrent_task_id ?? null,
                'is_conflict' => false,
            ];
        }

        foreach ($tasksByDate as $dateKey => &$tasks) {
            foreach ($tasks as &$task) {
                $timeKey = $task['scheduled_time'] ?? 'no_hora';
                $task['is_conflict'] = ($timeCounterByDate[$dateKey][$timeKey] ?? 0) > 1;
            }
        }
        unset($tasks);

        // DAILY (with filters)
        $dailyTasks = $this->taskRepository->getUserTasksByDate($userId, $date, $filters);

        // Count tasks by time slot for daily view
        $timeCounts = [];
        foreach ($dailyTasks as $t) {
            $key = $t->scheduled_time
                ? ( $t->scheduled_time instanceof Carbon
                    ? $t->scheduled_time->format('H:i')
                    : Carbon::parse((string)$t->scheduled_time)->format('H:i') )
                : 'no_hora';
            $timeCounts[$key] = ($timeCounts[$key] ?? 0) + 1;
        }

        // Special days (holidays, vacations, legal absences)
        $specialDays = $this->getSpecialDays($userId);

        // Calendar colors from enum
        $calendarColors = CalendarColor::taskLegend();

        return [
            'tasksByDate'   => $tasksByDate,
            'tasksDaily'    => $dailyTasks,
            'timeCounts'    => $timeCounts,
            'hasAnyTasks'   => $this->taskRepository->hasAnyTasks($userId),
            'specialDays'   => $specialDays,
            'calendarColors'=> $calendarColors,
        ];
    }

    /**
     * Build a map of special days for the given user.
     *
     * The returned array keys are date strings (Y-m-d) and values are strings:
     * 'holiday', 'vacation' or 'legal_absence'.
     *
     * @param int $userId
     * @return array<string,string>
     */
    public function getSpecialDays(int $userId): array
    {
        $specialDays = [];

        // 1) Holidays from user's work calendar template (if any)
        $user = $this->userRepository->find($userId);
        $templateId = $user->work_calendar_template_id ?? null;

        if ($templateId) {
            $workCalendarDays = $this->workCalendarDayRepository->getHolidaysByTemplate($templateId);
            foreach ($workCalendarDays as $day) {
                $dateKey = Carbon::parse($day->date)->toDateString();
                $specialDays[$dateKey] = 'holiday';
            }
        }

        // 2) User absences (vacation, legal_absence)
        $absences = $this->userAbsenceRepository->getUserAbsences($userId);
        foreach ($absences as $abs) {
            $dateKey = Carbon::parse($abs->date)->toDateString();
            if ($abs->type === 'vacation') {
                $specialDays[$dateKey] = 'vacation';
            } elseif ($abs->type === 'legal_absence') {
                $specialDays[$dateKey] = 'legal_absence';
            }
        }

        return $specialDays;
    }

    /**
     * Check that the submitted subtasks array contains at least one subtask with a non-empty title.
     *
     * @param array $subtasks
     * @return bool
     */
    public function hasValidSubtasksArray(array $subtasks): bool
    {
        foreach ($subtasks as $s) {
            if (!empty($s['title'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Upload pictogram image if it's an UploadedFile, otherwise return path as-is.
     *
     * @param UploadedFile|string|null $file
     * @param string|null $oldPath
     * @return string|null
     */
    public function normalizePictogram(UploadedFile|string|null $file, ?string $oldPath = null): ?string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        if ($file instanceof UploadedFile) {
            return $file->store('pictograms', 'public');
        }

        // If it's already a string path or null, just return it
        return $file ?? null;
    }

    /**
     * Store a pictogram provided as base64 and return its storage path.
     *
     * @param string $base64
     * @param string $filename
     * @return string|null
     */
    public function storeBase64Pictogram(string $base64, string $filename): ?string
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64)[1];
        }
        $imageData = base64_decode($base64);
        if (!$imageData) {
            return null;
        }

        // Ensure file has a valid extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'png';

        // Generate a short random name similar to UploadedFile::store behavior
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        $path = 'pictograms/' . $storedName;
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }

    /**
     * Normalize a single subtask (upload pictogram, ensure external_id).
     *
     * @param array $subtask
     * @return array
     */
    protected function normalizeSubtask(array $subtask): array
    {
        if (!empty($subtask['pictogram_path']) && $subtask['pictogram_path'] instanceof UploadedFile) {
            $subtask['pictogram_path'] = $this->normalizePictogram($subtask['pictogram_path']);
        }
        $subtask['external_id'] = $subtask['external_id'] ?? (string) Str::uuid();
        return $subtask;
    }

    /**
     * Normalize an array of subtasks.
     *
     * @param array $subtasks
     * @return array
     */
    protected function normalizeSubtasks(array $subtasks): array
    {
        return array_map(fn($st) => $this->normalizeSubtask($st), $subtasks);
    }


    /**
     * Create a task for a user with subtasks and optional recurrent data.
     *
     * @param int $userId
     * @param array $data
     * @return ?Task
     * @throws ValidationException
     */
    public function createTask(int $userId, array $data): ?Task
    {
        return DB::transaction(function () use ($userId, $data) {
            // Validate subtasks
            if (empty($data['subtasks']) || !$this->hasValidSubtasksArray($data['subtasks'])) {
                throw new \Exception('Debe añadir al menos una subtarea.');
            }

            // Check non-working day
            if (!empty($data['scheduled_date']) &&
                $this->isNonWorkingDay($userId, $data['scheduled_date'])) {
                throw ValidationException::withMessages([
                    'general' => ['El usuario está de vacaciones o tiene una ausencia legal en esta fecha.'],
                ]);
            }

            // Normalize pictogram and subtasks
            $data['pictogram_path'] = $this->normalizePictogram($data['pictogram_path'] ?? null);
            $data['subtasks'] = $this->normalizeSubtasks($data['subtasks']);

            // Create main task
            $task = $this->taskRepository->createFromData($userId, $data);

            if (empty($data['is_recurrent'])) {
                // Simple case: save task and subtasks
                $task->save();
                $this->createSubtasksForTask($task, $data['subtasks']);
                return $task->load('subtasks');
            }
            // Handle recurrent Tasks
            $this->handleRecurrentTask($task, $data);
            return $task->load('subtasks', 'recurrentTask');
        });
    }

    /**
     * Create subtasks for a given task.
     *
     * @param Task $task
     * @param array $subtasks
     * @return void
     */
    protected function createSubtasksForTask(Task $task, array $subtasks): void
    {
        foreach ($subtasks as $subtask) {
            $this->subtaskRepository->createManyFromArray($task, $subtask);
        }
    }

    /**
     * Handles recurrent task creation and instance generation.
     *
     * @param Task $firstTask
     * @param array $data
     * @return void
     */
    protected function handleRecurrentTask(Task $firstTask, array $data): void
    {
        $recurrentStartDate = Carbon::parse($data['recurrent_start_date']);
        $scheduledDay  = strtolower($recurrentStartDate->format('l'));
        $daysOfWeek    = array_map('strtolower', $data['days_of_week'] ?? []);

        $firstTaskExists = $firstTask && $firstTask->exists;

        if ($firstTaskExists && in_array($scheduledDay, $daysOfWeek)) {
            $firstTask->save();
            $this->createSubtasksForTask($firstTask, $data['subtasks']);
        }

        $recurrentId = $this->createRecurrentFromData($firstTask, $data);

        if ($firstTaskExists) {
            $firstTask->update(['recurrent_task_id' => $recurrentId]);
        }
    }

    /**
     * Create a recurrent task and generate instances with subtasks.
     *
     * @param Task $firstTask
     * @param array $data
     * @return int
     */
    protected function createRecurrentFromData(Task $firstTask, array $data): int
    {
        $userId = $firstTask->user_id;

        // Normalize main task pictogram if firstTask doesn't exist yet
        if (!$firstTask->exists && !empty($data['pictogram_path'])) {
            $data['pictogram_path'] = $this->normalizePictogram($data['pictogram_path']);
        }

        // Create recurrent task definition
        $recurrent = $this->recurrentTaskRepository->createFromData($userId, $data);

        // Generate dates for the recurrence
        $startDate = Carbon::parse($data['recurrent_start_date']);
        $endDate   = Carbon::parse($data['recurrent_end_date']);
        $dates     = $this->generateDatesForRecurrentTask($data['days_of_week'], $startDate, $endDate);

        $firstTaskDate = $firstTask->scheduled_date ? Carbon::parse($firstTask->scheduled_date) : null;

        // Prepare subtasks if first task is not saved
        $preparedSubtasks = !$firstTask->exists
            ? $this->normalizeSubtasks($data['subtasks'] ?? [])
            : [];

        foreach ($dates as $date) {
            $dateCarbon = Carbon::parse($date);

            // Skip the first task date
            if ($firstTaskDate && $dateCarbon->isSameDay($firstTaskDate)) {
                continue;
            }

            // Skip non-working days
            if ($this->isNonWorkingDay($firstTask->user_id, $date)) {
                continue;
            }

            if ($firstTask->exists) {
                // Normal case: replicate the first task and its subtasks
                $task = $this->taskRepository->replicateWithDate($firstTask, $recurrent->id, $date);
                $this->subtaskRepository->replicateMany($firstTask->subtasks, $task);
            } else {
                // Create instance task from normalized data
                $instanceData = $data;
                $instanceData['scheduled_date'] = $date;

                $instanceTask = $this->taskRepository->createFromData($userId, $instanceData);
                $instanceTask->save();
                $instanceTask->update(['recurrent_task_id' => $recurrent->id]);

                // Create subtasks
                foreach ($preparedSubtasks as $pst) {
                    $this->subtaskRepository->createManyFromArray($instanceTask, $pst);
                }
            }
        }

        return $recurrent->id;
    }

    /**
     * Generate all dates for a recurrent task based on rules.
     *
     * @param array $daysOfWeek
     * @param string|Carbon $startDate
     * @param string|null $endDate
     * @return array
     */
    protected function generateDatesForRecurrentTask(array $daysOfWeek, string|Carbon $startDate, ?string $endDate): array
    {
        $dates = [];
        $current = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        $end = $endDate ? Carbon::parse($endDate) : $current->copy()->addMonths(6);

        // Map English weekday keys to ISO numbers
        $weekdayMap = [
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7,
        ];

        $daysOfWeekIso = array_map(fn($day) => $weekdayMap[strtolower($day)] ?? null, $daysOfWeek);
        $daysOfWeekIso = array_filter($daysOfWeekIso);

        while ($current->lte($end)) {
            // Only include if current day is selected AND after startDate
            if (in_array($current->dayOfWeekIso, $daysOfWeekIso) && $current->gte($startDate)) {
                $dates[] = $current->toDateString();
            }
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Return a JSON-serializable representation of a task with subtasks and recurrent data.
     *
     * @param Task $task
     * @return array
     */
    public function getTaskJson(Task $task): array
    {
        $task->load(['subtasks', 'recurrentTask']);

        return [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'scheduled_date' => $task->scheduled_date,
                'scheduled_time' => $task->scheduled_time,
                'estimated_duration_minutes' => $task->estimated_duration_minutes,
                'status' => $task->status,
                'pictogram_path' => $task->pictogram_path,
                'is_recurrent' => (bool) $task->recurrentTask,
                'days_of_week' => $task->recurrentTask?->days_of_week ? json_decode($task->recurrentTask->days_of_week, true) : [],
                'recurrent_start_date' => optional($task->recurrentTask)->start_date,
                'recurrent_end_date' => optional($task->recurrentTask)->end_date,
                'subtasks' => $task->subtasks->map(function ($st) {
                    return [
                        'id' => $st->id,
                        'title' => $st->title,
                        'description' => $st->description,
                        'note' => $st->note,
                        'order' => $st->order,
                        'status' => $st->status,
                        'pictogram_path' => $st->pictogram_path,
                    ];
                })->toArray(),
            ],
        ];
    }

    /**
     * Update a task and optionally propagate changes to its recurrence series.
     *
     * @param Task $task The base task being updated
     * @param array $data The form data (task fields + subtasks)
     * @param bool $editSeries Whether to update the entire recurrence series
     * @return Task|Collection
     */
    public function updateTask(Task $task, array $data, bool $editSeries = false): Task|Collection
    {
        return DB::transaction(function () use ($task, $data, $editSeries) {
            // Sync subtasks of the base task
            if (isset($data['subtasks'])) {
                $this->syncSubtasks($task, $data['subtasks']);
                $task->load('subtasks');
            }

            // Prepare update payload for the task
            $updateData = $this->buildTaskUpdateData($task, $data, $editSeries);

            // Update the base task
            $task->update($updateData);

            // If editing the whole series, update all future tasks
            if ($editSeries && $task->recurrent_task_id) {
                $this->updateFutureTasksInSeries($task, $data);
            }

            // Return updated task(s)
            return $editSeries
                ? $this->taskRepository
                    ->getFutureRecurrentTasks($task->recurrent_task_id, now()->toDateString())
                    ->load('subtasks', 'recurrentTask')
                : $task->load('subtasks', 'recurrentTask');
        });
    }

    /**
     * Build the update payload for a task, merging existing values and new input.
     *
     * @param Task $task
     * @param array $data
     * @param bool $editSeries
     * @return array<string,mixed>
     */
    protected function buildTaskUpdateData(Task $task, array $data, bool $editSeries): array
    {
        $updateData = [
            'title'                 => $data['title'] ?? $task->title,
            'color'                 => $data['color'] ?? $task->color,
            'description'           => $data['description'] ?? $task->description,
            'scheduled_time'        => $data['scheduled_time'] ?? $task->scheduled_time,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? $task->estimated_duration_minutes,
            'pictogram_path'        => isset($data['pictogram_path'])
                ? $this->normalizePictogram($data['pictogram_path'], $task->pictogram_path)
                : $task->pictogram_path,
            'status'                => $data['status'] ?? $task->status,
            'notifications_enabled' => $data['notifications_enabled'] ?? $task->notifications_enabled,
            'reminder_minutes'      => $data['reminder_minutes'] ?? $task->reminder_minutes,
        ];

        // Only override scheduled_date for single edits
        if (!$editSeries) {
            $updateData['scheduled_date'] = $data['scheduled_date'] ?? $task->scheduled_date;
        }

        return $updateData;
    }

    /**
     * Update all future tasks in a recurrence series to match the base task.
     *
     * @param Task $baseTask
     * @param array $data
     * @return void
     */
    protected function updateFutureTasksInSeries(Task $baseTask, array $data): void
    {
        $recurrent = $baseTask->recurrentTask ?? new RecurrentTask();
        $recurrent->fill([
            'start_date'   => $data['recurrent_start_date'] ?? $recurrent->start_date,
            'end_date'     => $data['recurrent_end_date'] ?? $recurrent->end_date,
            'days_of_week' => json_encode($data['days_of_week'])  ?? $recurrent->days_of_week,
        ]);
        $recurrent->save();

        $startDate = Carbon::parse($recurrent->start_date);
        $endDate = Carbon::parse($recurrent->end_date);

        $allCandidateDates = collect(
            $this->generateDatesForRecurrentTask($data['days_of_week'], $startDate, $endDate)
        )->map(fn($d) => Carbon::parse($d)->toDateString());

        // Fechas que deberían existir según el nuevo rango
        $expectedDates = $allCandidateDates
            ->reject(fn($date) => $this->isNonWorkingDay($baseTask->user_id, $date))
            ->values()
            ->toArray();

        // Todas las tareas del recurrente (pasadas, presentes y futuras)
        $allTasks = $this->taskRepository->getTasksByRecurrentId($recurrent->id);

        $existingDates = $allTasks->pluck('scheduled_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // 1. Eliminar las que sobran
        $datesToDelete = array_diff($existingDates, $expectedDates);
        $this->taskRepository->deleteByDates($recurrent->id, $datesToDelete);

        // 2. Actualizar las que permanecen (excepto la base)
        foreach ($allTasks as $task) {
            $taskDate = Carbon::parse($task->scheduled_date)->toDateString();

            if ($task->id === $baseTask->id || !in_array($taskDate, $expectedDates)) {
                continue;
            }

            $updateData = $this->buildTaskUpdateData($task, $data, true);
            $task->update($updateData);

            // Eliminar subtasks existentes antes de replicar
            $task->subtasks()->delete();

            // Replicar subtasks desde la base
            $this->subtaskRepository->replicateMany($baseTask->subtasks, $task);
        }

        // 3. Crear tareas faltantes
        $datesToCreate = array_diff($expectedDates, $existingDates);
        foreach ($datesToCreate as $date) {
            $newTask = $this->taskRepository->replicateWithDate($baseTask, $recurrent->id, $date);
            $this->subtaskRepository->replicateMany($baseTask->subtasks, $newTask);
        }
    }

    protected function calculateFutureDates(string $startDate, string $endDate, array $daysOfWeek): array
    {
        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            if (in_array(strtolower($current->format('l')), $daysOfWeek)) {
                $dates[] = $current->toDateString();
            }
            $current->addDay();
        }

        return $dates;
    }


    /**
     * Delete a task instance or an entire series based on the user's choice.
     *
     * @param int $userId
     * @param int $taskId
     * @param bool $deleteSeries
     * @return void
     */
    public function deleteTask(int $userId, int $taskId, bool $deleteSeries): void
    {
        $task = $this->taskRepository->findById($userId, $taskId);

        if ($deleteSeries && $task->recurrent_task_id) {
            // Delete all future instances of the series starting from today
            $tasks = $this->taskRepository->getFutureRecurrentTasks(
                $task->recurrent_task_id,
                now()->toDateString()
            );
            $this->deleteTasksWithFiles($tasks);
        } else {
            // Delete only this task instance (manual delete allowed even if in the past)
            $this->deleteTasksWithFiles($task);
        }
    }

    /**
     * Delete tasks (or a single task) with their subtasks and associated files.
     *
     * @param Task|Collection $tasks
     * @return int
     */
    public function deleteTasksWithFiles(Task|Collection $tasks): int
    {
        $tasks = $tasks instanceof Task ? collect([$tasks]) : $tasks;

        return DB::transaction(function () use ($tasks) {
            $count = 0;
            foreach ($tasks as $task) {
                //Delete subtasks with pictograms
                $this->subtaskRepository->deleteWithFiles($task->subtasks);

                // Delete task with pictograms
                $this->taskRepository->deleteWithFiles($task);

                $count++;
            }
            return $count;
        });
    }

    /**
     * Synchronize subtasks: update existing, create new, delete removed.
     *
     * Expected input: array of subtasks with optional 'id' for existing subtasks and
     * optional 'pictogram' (UploadedFile) per subtask.
     *
     * @param Task $task
     * @param array<int,array> $formSubtasks
     * @return void
     */
    public function syncSubtasks(Task $task, array $formSubtasks): void
    {
        $submittedExternalIds = [];

        foreach ($formSubtasks as $index => $subtaskData) {
            $externalId = (string)($subtaskData['external_id'] ?? Str::uuid());
            $existing   = $task->subtasks()->firstWhere('external_id', $externalId);

            $values = [
                'title'       => $subtaskData['title'] ?? '',
                'description' => $subtaskData['description'] ?? '',
                'note'        => $subtaskData['note'] ?? '',
                'order'       => $subtaskData['order'] ?? $index,
                'status'      => $subtaskData['status'] ?? 'pending',
                'external_id' => $externalId,
            ];

            // pictogram logic (business rule)
            if (!empty($subtaskData['pictogram'])) {
                $values['pictogram_path'] = $this->normalizePictogram(
                    $subtaskData['pictogram'],
                    $existing?->pictogram_path
                );
            } elseif ($existing) {
                $values['pictogram_path'] = $existing->pictogram_path;
            }

            // delegate persistence to repository
            $this->subtaskRepository->updateOrCreate($task, $externalId, $values);
            $submittedExternalIds[] = $externalId;
        }

        // delete missing subtasks
        $this->subtaskRepository->deleteAllExcept($task, $submittedExternalIds);
    }

    /**
     * Return data required for the task detail partial (tasks for day + time counts).
     *
     * @param Task $task
     * @param string|null $date ISO date string. If null, use task scheduled_date or today.
     * @return array{task:Task, user:\App\Models\User, date:string, timeCounts:array<string,int>}
     */
    public function getTaskDetailData(Task $task, ?string $date = null): array
    {
        $task->load('subtasks');
        $user = $task->user;
        $dateToUse = $date ?? optional($task->scheduled_date)->format('Y-m-d') ?? now()->toDateString();

        $tasksForDay = Task::where('user_id', $user->id)
            ->whereDate('scheduled_date', $dateToUse)
            ->get();

        $timeCounts = $tasksForDay->groupBy(function ($t) {
            if ($t->scheduled_time instanceof Carbon) {
                return $t->scheduled_time->format('H:i');
            }
            return $t->scheduled_time ? Carbon::parse($t->scheduled_time)->format('H:i') : 'no_hora';
        })->map(fn($g) => $g->count())->toArray();

        return [
            'task' => $task,
            'user' => $user,
            'date' => $dateToUse,
            'timeCounts' => $timeCounts,
        ];
    }

    /**
     * Get daily tasks (paginated) for AJAX/daily endpoint.
     *
     * @param int $userId
     * @param string $date
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getDailyTasks(int $userId, string $date, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->getUserTasksByDate($userId, $date, $filters);
    }

    /**
     * Determine if a user already has a task scheduled for the given date and time.
     *
     * @param int $userId
     * @param string $date
     * @param string $time
     * @return bool
     */
    public function hasConflict(int $userId, string $date, string $time): bool
    {
        return $this->taskRepository->existsForUserAtDateTime($userId, $date, $time);
    }

    /**
     * Determine if the given date is a non-working day for the user.
     *
     * Non-working is defined as:
     *  - the user has a vacation / legal_absence recorded,
     *
     * @param int    $userId
     * @param string $date Date in 'Y-m-d' format
     * @return bool
     */
    public function isNonWorkingDay(int $userId, string $date): bool
    {
        return $this->userAbsenceRepository->hasAbsence($userId, $date);
    }

    /**
     * Get the selected days ready for the edit view.
     *
     * @param string|null $daysJson JSON string of weekdays, e.g., '["monday","tuesday"]'
     * @return array<string> Array of English weekday keys, e.g., ['monday','tuesday']
     */
    public function getSelectedDays(?string $daysJson): array
    {
        if (!$daysJson) return [];

        $decoded = json_decode($daysJson, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get available task colors.
     *
     * @return array<int, string>
     */
    public function getColors(): array
    {
        return Task::getColors();
    }

    /**
     * Formats a collection of tasks to display in a cloned selector.
     *
     * Each resulting option will have the format:
     *    "Username - Task Title - dd/mm/YYYY"
     *
     * @param User $user
     * @return SupportCollection
     */
    public function formatExistingTasks(User $user): SupportCollection
    {
        $tasks = $this->taskRepository->getForUserWithRelations($user->id);

        return $tasks->mapWithKeys(function ($task) {
            $formattedDate = $task->scheduled_date
                ? Carbon::parse($task->scheduled_date)->format('d/m/Y')
                : 'Sin fecha';

            $label = "{$task->user->name} - {$task->title} - {$formattedDate}";

            return [$task->id => $label];
        })->prepend('Selecciona una tarea para clonar', '');
    }

    /**
     * Gets all users that can be assigned to tasks (role 'user').
     *
     * @return SupportCollection
     */
    public function getAssignableUsers(): SupportCollection
    {
        return $this->userRepository->getAllUsersWithRoleUser();
    }

    /**
     * It determines whether a day is a holiday or not from a work calendar.
     *
     *
     * @param int $userId
     * @param string $date
     * @return bool
     */
    public function isFestiveDay(int $userId, string $date): bool
    {
        $templateId = $this->getTemplateId($userId);
        if (!$templateId) return false;

        return $this->workCalendarDayRepository->isFestiveDay($templateId, $date);
    }

    /**
     * It determines whether a day is a holiday or not from a work calendar.
     *
     * @param int $templateId
     * @return array
     */
    public function isFestiveDays(int $templateId): array
    {
        return $this->workCalendarDayRepository->getHolidaysByTemplate($templateId, 'array');
    }

    /**
     * Get work_calendar_template_id from a specific user
     *
     * @param int $userId
     * @return int|null
     */
    public function getTemplateId(int $userId): int|null
    {
        $user = $this->userRepository->find($userId);
        return $user->work_calendar_template_id ?? null;
    }

    /**
     * Processes a pictogram: if it is a base64 file, it saves it, if it is an existing path it duplicates it.
     *
     * @param string|UploadedFile|null $input
     * @param string|null $filename
     * @return string|null
     */
    public function processPictogram(string|UploadedFile|null $input, ?string $filename = null): ?string
    {
        // base64
        if (is_string($input) && $filename) {
            return $this->storeBase64Pictogram($input, $filename);
        }

        // UploadedFile
        if ($input instanceof UploadedFile) {
            return $input->store('pictograms', 'public');
        }

        // path
        if (is_string($input) && Storage::disk('public')->exists($input)) {
            $extension = pathinfo($input, PATHINFO_EXTENSION);
            $newPath = 'pictograms/' . Str::random(32) . '.' . $extension;
            Storage::disk('public')->copy($input, $newPath);
            return $newPath;
        }


        return null;
    }
}
