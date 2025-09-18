<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository class for handling database interactions related to the Role entity.
 */
class RoleRepository
{
    /**
     * Find a role by its ID.
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role
    {
        return Role::findOrFail($id);
    }
    /**
     * Find a role by its name.
     *
     * @param string $roleName
     * @return Role|null
     */
    public function findByName(string $roleName): ?Role
    {
        return Role::where('role_name', $roleName)->first();
    }

    /**
     * Get the ID of a role by its name.
     *
     * @param string $roleName
     * @return int|null
     */
    public function getIdByName(string $roleName): ?int
    {
        return $this->findByName($roleName)?->id;
    }

    /**
     * Get multiple roles by an array of role names.
     *
     * @param array $roleNames
     * @return Collection
     */
    public function findByNames(array $roleNames)
    {
        return Role::whereIn('role_name', $roleNames)->get();
    }

}
