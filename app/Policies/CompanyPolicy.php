<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_company');
    }

    /**
     * Determine whether the user can view a specific company.
     *
     * @param  User     $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('view_company');
    }

    /**
     * Determine whether the user can create companies.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_company');
    }

    /**
     * Determine whether the user can update the company.
     *
     * @param  User     $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->hasPermission('edit_company');
    }

    /**
     * Determine whether the user can delete the company.
     *
     * @param  User     $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermission('delete_company');
    }
}
