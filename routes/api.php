<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\SubtaskApiController;
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

    /*
    |--------------------------------------------------------------------------
    | Task
    |--------------------------------------------------------------------------
    */
    /*Route::get('/tasks', [TaskApiController::class, 'allTasksWithSubtasks']);
    Route::get('/tasks/today', [TaskApiController::class, 'tasksToday']);
    Route::get('/tasks/planned/{num}', [TaskApiController::class, 'plannedTasks'])->whereNumber('num');
    Route::get('/tasks/{task_id}/subtasks', [SubtaskApiController::class, 'index']);
        Route::get('/tasks/status/summary', [TaskApiController::class, 'statusSummary']);
    Route::get('/tasks/by-date/{date}', [TaskApiController::class, 'tasksByDate'])
        ->where('date', '\d{4}-\d{2}-\d{2}');*/
    Route::get('/tasks/{task_id}', [TaskApiController::class, 'show']);
    Route::put('/subtasks/{subtask_id}/status/{status}', [SubtaskApiController::class, 'updateStatus'])
        ->where('status', 'completed|pending');
    Route::get('/tasks/day-offset/{offset}', [TaskApiController::class, 'tasksByDayOffset'])
        ->where('offset', '[0-9]+');

    /*
    |--------------------------------------------------------------------------
    | Calendar / vacation, holiday
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar/colors', [CalendarApiController::class, 'getColors']);
    Route::get('/calendar/{type}', [CalendarApiController::class, 'getCalendarByType']);

    /*
   |--------------------------------------------------------------------------
   | Auth
   |--------------------------------------------------------------------------
   */
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Refresh token
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
