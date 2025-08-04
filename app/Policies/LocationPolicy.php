<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any locations.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_location');
    }

    /**
     * Determine whether the user can view a specific location.
     *
     * @param  User     $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('view_location');
    }

    /**
     * Determine whether the user can create locations.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_location');
    }

    /**
     * Determine whether the user can update the location.
     *
     * @param  User     $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->hasPermission('edit_location');
    }

    /**
     * Determine whether the user can delete the location.
     *
     * @param  User     $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermission('delete_location');
    }
}
