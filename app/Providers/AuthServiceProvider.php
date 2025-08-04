<?php

namespace App\Providers;

use App\Policies\LocationPolicy;
use App\Policies\TaskPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\LogPolicy;
use App\Models\Task;
use App\Models\Role;
use App\Models\User;
use App\Models\Log;
use App\Models\Location;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        Log::class => LogPolicy::class,
        Location::class => LocationPolicy::class
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
