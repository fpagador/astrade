<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\UserTaskController;
use App\Http\Controllers\Web\Admin\CalendarController;
use App\Http\Controllers\Web\Admin\NotificationController;
use App\Http\Controllers\Web\Admin\LogController;
use App\Http\Controllers\Web\Admin\CompanyController;
use App\Http\Controllers\Web\Admin\TaskCompletionLogController;
use Illuminate\Http\Request;
use App\Models\Task;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', 'login');

Route::get('/dashboard', function () {
    return view('web.admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Administration Panel
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|manager'])->prefix('admin')->name('admin.')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/show', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('users/{user}/edit-password', [UserController::class, 'editPassword'])->name('users.edit-password');
    Route::put('users/{user}/update-password', [UserController::class, 'updatePassword'])->name('users.update-password');
    Route::post('users/validate-field', [UserController::class, 'validateField'])
        ->name('users.validate-field');

    /*
    |--------------------------------------------------------------------------
    | Tasks by users
    |--------------------------------------------------------------------------
    */
    Route::get('/users/{id}/tasks', [UserTaskController::class, 'index'])->name('users.tasks');
    Route::get('/users/task/create/{userId?}', [UserTaskController::class, 'create'])->name('tasks.create');
    Route::get('/users/task/{task}/json', [UserTaskController::class, 'json'])->name('tasks.json');
    Route::post('/users/{id}/task', [UserTaskController::class, 'store'])->name('tasks.store');
    Route::delete('users/{user}/tasks/{task?}', [UserTaskController::class, 'destroy'])->name('users.tasks.destroy');
    Route::get('/tasks/{id}/edit', [UserTaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [UserTaskController::class, 'update'])->name('tasks.update');

    Route::get('users/{userId}/tasks/check-conflict', function($userId, Request $request) {
        $date = $request->query('scheduled_date');
        $time = $request->query('scheduled_time');

        $exists = Task::where('user_id', $userId)
            ->where('scheduled_date', $date)
            ->where('scheduled_time', $time)
            ->exists();

        return response()->json(['conflict' => $exists]);
    });

    /*
    |--------------------------------------------------------------------------
    | Work Calendar
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificaciones/crear', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notificaciones', [NotificationController::class, 'store'])->name('notifications.store');

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

    /*
    |--------------------------------------------------------------------------
    | Task Completion Logs
    |--------------------------------------------------------------------------
    */
    Route::get('/tasks-completion-log', [TaskCompletionLogController::class, 'index'])->name('task_completion_logs.index');

    /*
    |--------------------------------------------------------------------------
    | logs
    |--------------------------------------------------------------------------
    */
    Route::get('/logs-completado', [LogController::class, 'index'])->name('logs.index');

});

require __DIR__.'/auth.php';
