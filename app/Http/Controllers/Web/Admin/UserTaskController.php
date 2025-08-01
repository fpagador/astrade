<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\UserTaskFilterRequest;
use App\Models\User;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class UserTaskController extends WebController
{
    /**
     * Display a list of tasks assigned to the specified user,
     * with optional filtering by scheduled date and status.
     *
     * @param int $userId
     * @param UserTaskFilterRequest  $request
     * @return View|RedirectResponse
     */
    public function index(int $userId, UserTaskFilterRequest $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($userId, $request) {
            $user = User::findOrFail($userId);
            // Base query with subtasks eager loaded
            $query = Task::with('subtasks')->where('user_id', $userId);

            // Check if user has any task at all (used for empty states)
            $hasAnyTasks = $query->exists();

            // Apply filters
            $request->validated();

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('scheduled_date')) {
                $scheduledDate = \Carbon\Carbon::parse($request->scheduled_date)->format('Y-m-d');
                $query->whereDate('scheduled_date', $scheduledDate);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $tasks = $query->orderBy('scheduled_date')->paginate(15);

            return view('web.admin.users.tasks', compact('user', 'tasks', 'hasAnyTasks'));
        }, route('admin.users.index'));
    }

    /**
     * Show the form for creating a new task for the given user.
     *
     * @param int $userId The ID of the user to assign the task to.
     * @return View The view for creating a new task.
     */
    public function create(int $userId): View
    {
        $user = User::findOrFail($userId);
        return view('web.admin.tasks.create', compact('user'));
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

            return redirect()->route('admin.users.tasks', $userId);
        }, route('admin.users.tasks', $userId), 'Tarea eliminada correctamente.');
    }

}
