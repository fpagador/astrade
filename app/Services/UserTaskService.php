<?php
namespace App\Services;

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
     * @param string $date ISO date string for daily view
     * @param array $filters Optional filters (title, status)
     * @return array {
     *      @type array  $tasksByDate   tasks grouped by scheduled_date (weekly view)
     *      @type \Illuminate\Contracts\Pagination\LengthAwarePaginator $tasksDaily paginated daily tasks
     *      @type array  $timeCounts    counts per scheduled_time for day
     *      @type bool   $hasAnyTasks
     *      @type array  $specialDays   map date => type (holiday, vacation, legal_absence)
     *      @type array  $calendarColors array of available calendar colors
     * }
     */
    public function getUserTasksForCalendar(int $userId, string $date, array $filters = []): array
    {
        // WEEKLY
        $weeklyTasks = $this->taskRepository->getUserTasks($userId);
        $tasksByDate = [];

        foreach ($weeklyTasks as $task) {
            $dateKey = $task->scheduled_date
                ? Carbon::parse($task->scheduled_date)->toDateString()
                : 'sin_fecha';

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
                'recurrent_task_id' => $task->recurrent_task_id ?? null
            ];
        }

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
        $calendarColors = CalendarColor::values();

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
     * Upload pictogram image and optionally delete previous path.
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @return string
     */
    public function handlePictogramUpload(UploadedFile $file, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        return $file->store('pictograms', 'public');
    }

    /**
     * Create a task for a user with subtasks and optional recurrent data.
     *
     * @param int $userId
     * @param array $data
     * @return Task
     * @throws \Exception
     */
    public function createTask(int $userId, array $data): Task
    {
        return DB::transaction(function () use ($userId, $data) {
            // Validate subtasks
            if (empty($data['subtasks']) || !$this->hasValidSubtasksArray($data['subtasks'])) {
                throw new \Exception('Debe añadir al menos una subtarea.');
            }

            // Check vacations on scheduled date
            if (!empty($data['scheduled_date']) &&
                $this->userAbsenceRepository->hasAbsence($userId, $data['scheduled_date'])) {
                throw new \Exception('El usuario está de vacaciones o tiene una ausencia legal en esta fecha.');
            }

            // Handle pictogram
            if (!empty($data['pictogram']) && $data['pictogram'] instanceof UploadedFile) {
                $data['pictogram_path'] = $this->handlePictogramUpload($data['pictogram']);
            }

            // Create main task
            $task = $this->taskRepository->createFromData($userId, $data);

            // Create subtasks
            $this->subtaskRepository->createManyFromArray($task, $data['subtasks']);

            // Handle recurrent series
            if (!empty($data['is_recurrent'])) {
                $recurrentId = $this->createRecurrentFromData($task, $data);
                $task->update(['recurrent_task_id' => $recurrentId]);
            }

            return $task->load('subtasks', 'recurrentTask');
        });
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
        $days = $data['days_of_week'];
        $startDate = Carbon::parse($data['recurrent_start_date']);
        $endDate   = Carbon::parse($data['recurrent_end_date']);

        // Create recurrent task definition
        $recurrent = $this->recurrentTaskRepository->createFromData($firstTask->user_id, $data);

        // Generate instances
        $dates = $this->generateDatesForRecurrentTask($days, $startDate, $endDate);

        // First task date as Carbon
        $firstTaskDate = $firstTask->scheduled_date ? Carbon::parse($firstTask->scheduled_date) : null;

        foreach ($dates as $date) {
            $dateCarbon = Carbon::parse($date);

            // Skip the first task date
            if ($firstTaskDate && $dateCarbon->isSameDay($firstTaskDate)) {
                continue;
            }

            // Skip if user has absence
            if ($this->userAbsenceRepository->hasAbsence($firstTask->user_id, $date)) {
                continue;
            }

            // Create instance
            $task = $this->taskRepository->replicateWithDate($firstTask, $recurrent->id, $date);

            // Copy subtasks
            $this->subtaskRepository->replicateMany($firstTask->subtasks, $task);
        }

        return $recurrent->id;
    }

    /**
     * Update or create recurrent series and regenerate future tasks if needed.
     *
     * @param Task $task
     * @param array $data
     * @param bool $editSeries
     * @return void
     */
    public function updateOrCreateRecurrent(Task $task, array $data, bool $editSeries = false): RecurrentTask
    {
        $recurrent = $task->recurrentTask ?? new RecurrentTask();

        // Save the original date of the recurrence
        $originalStartDate = $recurrent->start_date;
        $originalEndDate = $recurrent->end_date;

        $recurrent->fill([
            'start_date' => $data['recurrent_start_date'] ?? $recurrent->start_date,
            'end_date'   => $data['recurrent_end_date'] ?? $recurrent->end_date,
            'days'       => $data['days_of_week'] ?? $recurrent->days_of_week,
        ]);
        $recurrent->save();

        if ($editSeries) {
            $startDate = Carbon::parse($recurrent->start_date);
            $endDate = Carbon::parse($recurrent->end_date);

            // Check if the range has changed
            $rangeChanged = ($originalStartDate && $originalEndDate) &&
                ($originalStartDate != $recurrent->start_date || $originalEndDate != $recurrent->end_date);

            //Determine cutoffDate based on whether the range changed or not
            $today = now()->toDateString();

            if ($rangeChanged) {
                //New range: Create/edit tasks only from the largest date between today and the startDate
                $cutoffDate = max($today, $startDate->toDateString());
            } else {
                // Same rank: only modify from today
                $cutoffDate = $today;
            }

            // Generate all dates according to days of the week and new range
            $allNewDates = collect(
                $this->generateDatesForRecurrentTask($data['days_of_week'], $startDate, $endDate)
            );

            // Filter the dates to update/create based on cutoffDate
            $futureNewDates = $allNewDates->filter(fn($date) => $date >= $cutoffDate);

            // Get existing recurring tasks from cutoffDate
            $existingFutureTasks = $this->taskRepository->getFutureRecurrentTasks($recurrent->id, $cutoffDate);
            $existingFutureDates = $existingFutureTasks->pluck('scheduled_date')->toArray();

            // Delete dates that are no longer part of the plan
            $datesToDelete = array_diff($existingFutureDates, $futureNewDates->toArray());
            $this->taskRepository->deleteByDates($recurrent->id, $datesToDelete);

            //Create new missing dates
            $datesToCreate = array_diff($futureNewDates->toArray(), $existingFutureDates);
            foreach ($datesToCreate as $date) {
                $newTask = $this->taskRepository->replicateWithDate($task, $recurrent->id, $date);
                $this->subtaskRepository->replicateMany($task->subtasks, $newTask);
            }
        }

        return $recurrent;
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
     * Update a task (normal or recurrent) and sync subtasks and recurrent data.
     *
     *
     * @param Task $task
     * @param array $data
     * @param bool $editSeries
     * @return Task|Collection
     * @throws \Exception
     */
    public function updateTask(Task $task, array $data, bool $editSeries = false): Task|Collection
    {
        return DB::transaction(function () use ($task, $data, $editSeries) {
            if ($editSeries && $task->recurrent_task_id && !empty($data['is_recurrent'])) {
                //updateOrCreateRecurrent will create/delete future instances based on the new definition
                $recurrent = $this->updateOrCreateRecurrent($task, $data, true);

                // We calculate cutoffDate = max(today, new start_date)
                $startDate = $recurrent->start_date;
                $cutoffDate = Carbon::parse($startDate)->lt(now())
                    ? now()->toDateString()
                    : Carbon::parse($startDate)->toDateString();

                // We get ONLY the recurring tasks from cutoffDate onwards (the ones we need to update)
                $tasksToUpdate = $this->taskRepository->getFutureRecurrentTasks($recurrent->id, $cutoffDate);
            } else {
                //If it is not a serial edition, behavior as before:
                $tasksToUpdate = ($editSeries && $task->recurrent_task_id)
                    ? $this->taskRepository->getAllRecurrentTasks($task->recurrent_task_id)
                    : collect([$task]);
            }

            // Now we update ONLY the tasks we have chosen (if editSeries, they will be the future ones since cutoff)
            foreach ($tasksToUpdate as $t) {
                // Handle main pictogram upload if provided
                if (!empty($data['pictogram_path'])) {
                    $data['pictogram_path'] = $this->handlePictogramUpload(
                        $data['pictogram_path'],
                        $t->pictogram_path
                    );
                }

                // Build update payload with fallbacks to existing values
                $updateData = [
                    'title' => $data['title'] ?? $t->title,
                    'color' => $data['color'] ?? $t->color,
                    'description' => $data['description'] ?? $t->description,
                    'scheduled_time' => $data['scheduled_time'] ?? $t->scheduled_time,
                    'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? $t->estimated_duration_minutes,
                    'pictogram_path' => $data['pictogram_path'] ?? $t->pictogram_path,
                    'status' => $data['status'] ?? $t->status,
                    'notifications_enabled' => $data['notifications_enabled'] ?? $t->notifications_enabled,
                    'reminder_minutes' => $data['reminder_minutes'] ?? $t->reminder_minutes,
                ];

                // Update scheduled_date only for single-instance edits (no editar la serie)
                if (!$editSeries) {
                    $updateData['scheduled_date'] = $data['scheduled_date'] ?? $t->scheduled_date;
                }

                // Update the task in DB
                $t->update($updateData);

                // Sync subtasks:
                // - If editSeries => sync subtasks on each future updated instance,
                // - If not editSeries => sync only for the original edited task.
                if (isset($data['subtasks'])) {
                    if ($editSeries) {
                        $this->syncSubtasks($t, $data['subtasks']);
                    } else {
                        if ($t->id === $task->id) {
                            $this->syncSubtasks($t, $data['subtasks']);
                        }
                    }
                }

            }

            // Return updated tasks or the single instance
            return $editSeries
                ? $tasksToUpdate->load('subtasks', 'recurrentTask')
                : $task->load('subtasks', 'recurrentTask');
        });
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
                // Delete subtasks pictograms
                foreach ($task->subtasks as $subtask) {
                    if ($subtask->pictogram_path && Storage::disk('public')->exists($subtask->pictogram_path)) {
                        Storage::disk('public')->delete($subtask->pictogram_path);
                    }
                }

                // Delete task pictogram
                if ($task->pictogram_path && Storage::disk('public')->exists($task->pictogram_path)) {
                    Storage::disk('public')->delete($task->pictogram_path);
                }

                // Delete task and subtasks
                $task->subtasks()->delete();
                $task->delete();
                $count++;
            }
            return $count;
        });
    }

    /**
     * Create subtasks for a given task.
     *
     * Each subtask array may include optionally an UploadedFile under key 'pictogram'.
     *
     * @param Task $task
     * @param array<int,array> $subtasks
     * @return void
     */
    public function createSubtasks(Task $task, array $subtasks): void
    {
        foreach ($subtasks as $index => $st) {
            $attrs = [
                'title' => $st['title'] ?? '',
                'description' => $st['description'] ?? null,
                'note' => $st['note'] ?? null,
                'order' => $st['order'] ?? $index,
                'status' => $st['status'] ?? 'pending',
            ];
            if (!empty($st['pictogram']) && $st['pictogram'] instanceof UploadedFile) {
                $attrs['pictogram_path'] = $this->handlePictogramUpload($st['pictogram']);
            } elseif (!empty($st['pictogram_path'])) {
                $attrs['pictogram_path'] = $st['pictogram_path'];
            }
            $task->subtasks()->create($attrs);
        }
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
        $submittedIds = [];
        foreach ($formSubtasks as $index => $subtaskData) {
            $id = !empty($subtaskData['id']) ? (int)$subtaskData['id'] : null;
            $values = [
                'title' => $subtaskData['title'] ?? '',
                'description' => $subtaskData['description'] ?? '',
                'note' => $subtaskData['note'] ?? '',
                'order' => $subtaskData['order'] ?? $index,
                'status' => $subtaskData['status'] ?? 'pending',
            ];

            // handle pictogram upload for this subtask if provided
            if (!empty($subtaskData['pictogram']) && $subtaskData['pictogram'] instanceof UploadedFile) {
                $oldPath = null;
                if ($id) {
                    $existing = $task->subtasks()->find($id);
                    $oldPath = $existing?->pictogram_path;
                }
                $values['pictogram_path'] = $this->handlePictogramUpload($subtaskData['pictogram'], $oldPath);
            }

            if ($id && ($existing = $task->subtasks()->find($id))) {
                $existing->update($values);
                $submittedIds[] = $existing->id;
            } else {
                $new = $task->subtasks()->create($values);
                $submittedIds[] = $new->id;
            }
        }

        // delete any subtasks not in submittedIds
        $task->subtasks()
            ->whereNotIn('id', $submittedIds)
            ->get()
            ->each(function ($sub) {
                if ($sub->pictogram_path && Storage::disk('public')->exists($sub->pictogram_path)) {
                    Storage::disk('public')->delete($sub->pictogram_path);
                }
                $sub->delete();
            });
    }

    protected function createRecurrentTask(Task $task, array $data): RecurrentTask
    {
        return RecurrentTask::create([
            'user_id' => $task->user_id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'days_of_week' => json_encode($data['days_of_week'] ?? [])
        ]);
    }

    protected function updateRecurrentTask(Task $task, array $data): void
    {
        if ($task->recurrentTask) {
            $task->recurrentTask->update([
                'start_date' => $data['start_date'] ?? $task->recurrentTask->start_date,
                'end_date' => $data['end_date'] ?? $task->recurrentTask->end_date,
                'days_of_week' => json_encode($data['days_of_week'] ?? []),
            ]);
        } else {
            $this->createRecurrentTask($task, $data);
        }
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
     *  - a holiday in the user's assigned work calendar template,
     *  - the user has a vacation / legal_absence recorded,
     *  - or the date falls on a weekend.
     *
     * @param int    $userId
     * @param string $date Date in 'Y-m-d' format
     * @return bool
     */
    public function isNonWorkingDay(int $userId, string $date): bool
    {
        // 1) Get user's calendar template id
        $templateId = $this->userRepository->getWorkCalendarTemplateId($userId);

        // 2) Check holiday for that template (if template assigned)
        $isHoliday = false;
        if ($templateId !== null) {
            $isHoliday = $this->workCalendarDayRepository->isHolidayForTemplate($templateId, $date);
        }

        // 3) Check user absences (vacation or legal_absence)
        $isAbsence = $this->userAbsenceRepository->hasAbsence($userId, $date);

        return $isHoliday || $isAbsence;
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
}
