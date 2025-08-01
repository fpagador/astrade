<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Policies\TaskPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\LogPolicy;
use App\Models\Task;
use App\Models\Role;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Task::class => TaskPolicy::class,
        Role::class => RolePolicy::class,
        User::class => UserPolicy::class,
        Log::class => LogPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Blade::if('permission', function ($permission) {
            return Auth::check() && Auth::user()->hasPermission($permission);
        });
    }
}
