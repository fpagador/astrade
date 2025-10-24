<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\Admin\UserTaskController;
use App\Http\Controllers\Web\Admin\UserAbsenceController;
use App\Http\Controllers\Web\Admin\LogController;
use App\Http\Controllers\Web\Admin\CompanyController;
use App\Http\Controllers\Web\Admin\WorkCalendarTemplateController;
use App\Http\Requests\Admin\StoreOrUpdateTaskRequest;
use App\Http\Controllers\Web\Admin\TaskController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', 'login');


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
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/tasks-by-day/{day}', [DashboardController::class, 'tasksByDay'])->name('tasks-by-day');
        Route::get('/tasks-by-user/{userId?}', [DashboardController::class, 'tasksByUser'])->name('tasks-by-user');
        Route::get('/employees-by-company/{companyId?}', [DashboardController::class, 'employeesByCompany'])
            ->name('employees-by-company');
        Route::get('/users-without-tasks/{day}', [DashboardController::class, 'usersWithoutTasks'])
            ->name('users-without-tasks');
        Route::get('users-by-performance/{day}/{range}', [DashboardController::class, 'getUsersByPerformance'])
            ->name('users-by-performance');
    });

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
        Route::get('/export', [UserController::class, 'export'])
            ->name('export')
            ->middleware('can:viewAdmin,App\Models\User');

        // Ajax methods to validate form fields
        Route::post('/validate-field', [UserController::class, 'validateField'])->name('validate-field');
        Route::post('/validate-password', [UserController::class, 'validatePassword'])->name('validate-password');

        // Ajax method to select the user who can be called
        Route::post('{user}/toggle-call', [UserController::class, 'toggleCall'])
            ->name('toggleCall')
            ->middleware('can:update,user');

        /*
        |--------------------------------------------------------------------------
        | Holidays and legal absence per user
        |--------------------------------------------------------------------------
        */
        Route::get('/{user}/absences', [UserAbsenceController::class, 'index'])->name('absences');
        Route::post('/{user}/absences', [UserAbsenceController::class, 'store'])->name('absences.store');
        Route::post('{user}/absences/check-save', [UserAbsenceController::class, 'checkAndSave'])
            ->name('absences.checkSave');

        /*
        |--------------------------------------------------------------------------
        | Tasks by users
        |--------------------------------------------------------------------------
        */
        Route::get('/{user}/tasks', [UserTaskController::class, 'index'])->name('tasks');
        Route::get('/task/create/{user?}', [UserTaskController::class, 'create'])->name('tasks.create');
        Route::get('/task/{task}/json', [UserTaskController::class, 'json'])->name('tasks.json');
        Route::post('/{user}/task', [UserTaskController::class, 'store'])->name('tasks.store');
        Route::delete('/{user}/tasks/{task?}', [UserTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::get('/task/{id}/edit', [UserTaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/task/{task}', [UserTaskController::class, 'update'])->name('tasks.update');

        Route::get('/{userId}/tasks/check-conflict', [UserTaskController::class, 'checkConflict']);
        Route::get('/{userId}/tasks/check-nonworking', [UserTaskController::class, 'checkNonWorking']);
        Route::get('/{userId}/tasks/check-nonworking-range', [UserTaskController::class, 'checkNonWorkingRange']);

        //USER TASKS CALENDAR
        Route::get('/tasks/{task}/detail', [UserTaskController::class, 'taskDetail'])->name('tasks.detail');
        Route::get('/{user}/tasks-daily', [UserTaskController::class, 'daily'])
            ->name('tasks.daily');

        Route::post('/tasks/validate', function (StoreOrUpdateTaskRequest $request) {
            return response()->json(['success' => true]);
        })->name('tasks.validate');
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
        Route::get('/{template}/show', [WorkCalendarTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [WorkCalendarTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [WorkCalendarTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [WorkCalendarTemplateController::class, 'destroy'])->name('destroy');

        // Managing days within the template
        Route::post('/{template}/days', [WorkCalendarTemplateController::class, 'addDay'])->name('days.add');
        Route::delete('/{template}/days/{day}', [WorkCalendarTemplateController::class, 'removeDay'])->name('days.remove');

        Route::get('/{template}/clone-data', [WorkCalendarTemplateController::class, 'cloneTemplateData'])
            ->name('clone-data');
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
    | Task Log
    |--------------------------------------------------------------------------
    */
    Route::get('/task-log', [TaskController::class, 'index'])->name('task_logs.index');
    Route::get('task-log/export', [TaskController::class, 'export'])
        ->name('task_logs.export')
        ->middleware(['auth', 'role:admin|manager']);
    /*
    |--------------------------------------------------------------------------
    | logs
    |--------------------------------------------------------------------------
    */
    Route::get('/logs-completado', [LogController::class, 'index'])->name('logs.index');

});

require __DIR__.'/auth.php';
