<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Allow users to manage their own profile, admins can manage anyone.
     *
     * @param  User  $user
     * @param  User  $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('view_user');
    }

    /**
     * Determine whether the user can create models.
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_user');
    }

    /**
     * Determine whether the user can update the model.
     * @param  User  $user
     * @param  User  $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('edit_user');
    }

    /**
     * Determine whether the user can delete the model.
     * @param  User  $user
     * @param  User  $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('delete_user') && $user->id !== $model->id;;
    }

    /**
     * Allow other users to change their passwords if they have the permission.
     * @param  User  $user
     * @param  User  $model
     * @return bool
     */
    public function changePassword(User $user, User $model): bool
    {
        return $user->hasPermission('change_password') && $user->id !== $model->id;
    }
}
