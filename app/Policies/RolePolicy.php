<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Only admins can manage roles.
     *
     * @param  User  $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('view_role');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_role');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->hasPermission('edit_role');
    }


    /**
     * Allow Admins to do everything automatically
     * @param  User  $user
     * @return bool
     */
    public function before(User $user): bool
    {
        return $user->roles->contains('role_name', 'admin') ? true : false;
    }
}
