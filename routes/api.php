<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\SubtaskApiController;
use App\Http\Controllers\Api\TaskCompletionLogApiController;
use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\PushNotificationController;
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
    Route::get('/profile/update', [UserApiController::class, 'update']);
    /*
    |--------------------------------------------------------------------------
    | Task
    |--------------------------------------------------------------------------
    */
    Route::get('/tasks/today', [TaskApiController::class, 'tasksToday']);
    Route::get('/tasks/plan/{num}', [TaskApiController::class, 'plannedTasks'])->whereNumber('num');
    Route::get('/tasks/{task_id}', [TaskApiController::class, 'show']);
    Route::get('/tasks/{task_id}/subtasks', [SubtaskApiController::class, 'index']);
    Route::patch('/subtasks/{subtask_id}/complete', [SubtaskApiController::class, 'complete']);
    Route::get('/tasks/status/summary', [TaskApiController::class, 'statusSummary']);

    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications/config', [NotificationApiController::class, 'config']);
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::post('/notifications/send', [NotificationApiController::class, 'sendNow']);

    /*
    |--------------------------------------------------------------------------
    | Calendar / vacation, holiday and sick_leave
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
   | Locations
   |--------------------------------------------------------------------------
   */
    // View locations associated with a task
    Route::get('/tasks/{task_id}/locations', [TaskApiController::class, 'getLocationsByTask']);

    // Associate location(s) to a task
    Route::post('/tasks/{task_id}/locations', [TaskApiController::class, 'attachLocations']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('push')->controller(PushNotificationController::class)->group(function () {
    Route::post('/to-device', 'sendToDevice');
    Route::post('/to-multiple', 'sendToMultiple');
    Route::post('/to-topic', 'sendToTopic');
});
