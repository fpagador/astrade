<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LogPolicy
{
    use HandlesAuthorization;

    /**
     * Allow users who have access to view the log table
     *
     * @param  User  $user
     * @return bool
     */
    public function viewLogs(User $user): bool
    {
        return $user->hasPermission('view_logs');
    }
}
