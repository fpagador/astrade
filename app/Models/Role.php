<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $role_name
 * @property-read Collection<User> $users
 * @property-read Collection<Permission> $permissions
 */
class Role extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['role_name'];

    /**
     * The users that belong to this role.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles','role_id', 'user_id');
    }

    /**
     * The permissions that belong to this role.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id','permission_id');
    }
}
