<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\SubtaskApiController;
use App\Http\Controllers\Api\TaskCompletionLogApiController;
use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;


// Public login
Route::post('/login', [AuthController::class, 'login']);

// Routes for authenticated mobile users (role: user)

Route::middleware(['auth:sanctum', 'role:admin|manager|user|'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [UserApiController::class, 'profile']);
    Route::get('/phone', [UserApiController::class, 'getPhone']);

    /*
    |--------------------------------------------------------------------------
    | Task
    |--------------------------------------------------------------------------
    */
    Route::get('/tasks', [TaskApiController::class, 'allTasksWithSubtasks']);
    Route::get('/tasks/today', [TaskApiController::class, 'tasksToday']);
    Route::get('/tasks/planned/{num}', [TaskApiController::class, 'plannedTasks'])->whereNumber('num');
    Route::get('/tasks/{task_id}', [TaskApiController::class, 'show']);
    Route::get('/tasks/{task_id}/subtasks', [SubtaskApiController::class, 'index']);
    Route::put('/subtasks/{subtask_id}/status/{status}', [SubtaskApiController::class, 'updateStatus'])
        ->where('status', 'completed|pending');
    Route::get('/tasks/status/summary', [TaskApiController::class, 'statusSummary']);

    /*
    |--------------------------------------------------------------------------
    | Calendar / vacation, holiday
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar/{type}', [CalendarApiController::class, 'getCalendarByType']);

    /*
   |--------------------------------------------------------------------------
   | Logs and Audit
   |--------------------------------------------------------------------------
   */
    Route::get('/logs/completions', [TaskCompletionLogApiController::class, 'taskCompletions']);

    /*
   |--------------------------------------------------------------------------
   | Companies
   |--------------------------------------------------------------------------
   */
    // View companies associated with a task
    Route::get('/tasks/{task_id}/companies', [TaskApiController::class, 'getCompaniesByTask']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Refresh token
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
