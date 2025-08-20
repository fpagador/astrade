<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\UserTaskController;
use App\Http\Controllers\Web\Admin\UserVacationController;
use App\Http\Controllers\Web\Admin\LogController;
use App\Http\Controllers\Web\Admin\CompanyController;
use App\Http\Controllers\Web\Admin\TaskCompletionLogController;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Http\Controllers\Web\Admin\WorkCalendarTemplateController;

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
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/show', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::get('/{user}/edit-password', [UserController::class, 'editPassword'])->name('edit-password');
        Route::put('/{user}/update-password', [UserController::class, 'updatePassword'])->name('update-password');
        Route::post('/validate-field', [UserController::class, 'validateField'])->name('validate-field');

        /*
        |--------------------------------------------------------------------------
        | Holidays per user
        |--------------------------------------------------------------------------
        */
        Route::get('/{user}/vacations', [UserVacationController::class, 'index'])->name('vacations');
        Route::post('/{user}/vacations', [UserVacationController::class, 'store'])->name('vacations.store');

        /*
        |--------------------------------------------------------------------------
        | Tasks by users
        |--------------------------------------------------------------------------
        */
        Route::get('/{id}/tasks', [UserTaskController::class, 'index'])->name('tasks');
        Route::get('/task/create/{userId?}', [UserTaskController::class, 'create'])->name('tasks.create');
        Route::get('/task/{task}/json', [UserTaskController::class, 'json'])->name('tasks.json');
        Route::post('/{id}/task', [UserTaskController::class, 'store'])->name('tasks.store');
        Route::delete('/{user}/tasks/{task?}', [UserTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::get('/task/{id}/edit', [UserTaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/task/{task}', [UserTaskController::class, 'update'])->name('tasks.update');

        Route::get('/{userId}/tasks/check-conflict', function($userId, Request $request) {
            $date = $request->query('scheduled_date');
            $time = $request->query('scheduled_time');

            $exists = Task::where('user_id', $userId)
                ->where('scheduled_date', $date)
                ->where('scheduled_time', $time)
                ->exists();

            return response()->json(['conflict' => $exists]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Work Calendar
    |--------------------------------------------------------------------------
    */
    Route::prefix('calendars')->name('calendars.')->group(function () {
        Route::get('/', [WorkCalendarTemplateController::class, 'index'])->name('index');
        Route::get('/create', [WorkCalendarTemplateController::class, 'create'])->name('create');
        Route::post('/', [WorkCalendarTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [WorkCalendarTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [WorkCalendarTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [WorkCalendarTemplateController::class, 'destroy'])->name('destroy');

        // Managing days within the template
        Route::post('/{template}/days', [WorkCalendarTemplateController::class, 'addDay'])->name('days.add');
        Route::delete('/{template}/days/{day}', [WorkCalendarTemplateController::class, 'removeDay'])->name('days.remove');
    });

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');
    });

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
