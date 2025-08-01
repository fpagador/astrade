<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\UserTaskController;
use App\Http\Controllers\Web\Admin\TaskController;
use App\Http\Controllers\Web\Admin\CalendarController;
use App\Http\Controllers\Web\Admin\NotificationController;
use App\Http\Controllers\Web\Admin\LogController;
use App\Http\Controllers\Web\Admin\RoleController;

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
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('users/{user}/edit-password', [UserController::class, 'editPassword'])->name('users.edit-password');
    Route::put('users/{user}/update-password', [UserController::class, 'updatePassword'])->name('users.update-password');

    Route::delete('users/{user}/tasks/{task?}', [UserTaskController::class, 'destroy'])->name('users.tasks.destroy');


    /*
    |--------------------------------------------------------------------------
    | Tasks by users
    |--------------------------------------------------------------------------
    */
    Route::get('/users/{id}/tasks', [UserTaskController::class, 'index'])->name('users.tasks');

    Route::prefix('users/{userId}/tasks')->name('users.tasks.')->group(function () {
        Route::get('create', [UserTaskController::class, 'create'])->name('create');
        Route::post('', [UserTaskController::class, 'store'])->name('store');
    });

    /*
    |--------------------------------------------------------------------------
    | Task Management
    |--------------------------------------------------------------------------
    */
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/users/task/create/{userId?}', [TaskController::class, 'create'])->name('tasks.create');
    Route::get('/users/task/{task}/json', [TaskController::class, 'json'])->name('tasks.json');
    Route::post('/users/{id}/task', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');


    /*
    |--------------------------------------------------------------------------
    | Work Calendar
    |--------------------------------------------------------------------------
    */
    Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar.index');

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
    | Completion logs
    |--------------------------------------------------------------------------
    */
    Route::get('/logs-completado', [LogController::class, 'index'])->name('logs.index');

});

require __DIR__.'/auth.php';
