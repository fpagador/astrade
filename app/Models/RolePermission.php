<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $role_id
 * @property int $permission_id
 * @property-read Role $role
 * @property-read Permission $permission
 */
class RolePermission extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $table = 'role_permissions';

    /** @var array<int, string> */
    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    /**
     * Get the role that owns this permission link.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the permission that is linked to this role.
     *
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
