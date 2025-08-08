<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $surname
 * @property string $dni
 * @property string $email
 * @property string $username
 * @property string $password
 * @property string|null $photo
 * @property string|null $work_schedule
 * @property string|null $contract_type
 * @property Carbon|null $contract_start_date
 * @property string|null $notification_type
 * @property bool $can_receive_notifications
 * @property int|null $role_id
 * @property-read Role|null $role
 * @property-read NotificationPreference|null $notificationPreferences
 * @property-read Collection<Calendar> $calendar
 * @property-read Collection<Notification> $notifications
 * @property-read Collection<Task> $tasks
 * @property-read Collection<TaskCompletionLog> $logs
 * @property-read Collection<Task> $assignedTasks
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'name', 'surname', 'dni', 'email', 'username', 'password', 'photo',
        'work_schedule', 'contract_type', 'contract_start_date',
        'notification_type', 'can_receive_notifications', 'role_id', 'phone'
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'password',
    ];

    /** @var array<int, string> */
    protected $casts = [
        'contract_start_date' => 'date',
        'can_receive_notifications' => 'boolean'
    ];

    /**
     * Get the tasks assigned *by* this user.
     *
     * @return HasMany
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    /**
     * Get the notification preferences for the user.
     *
     * @return HasOne
     */
    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(NotificationPreference::class, 'user_id', 'id');
    }

    /**
     * Get the notifications sent to the user.
     *
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's calendar entries.
     *
     * @return HasMany
     */
    public function calendar(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    /**
     * Get the logs related to the user's completed tasks or subtasks.
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TaskCompletionLog::class);
    }

    /**
     * Get the role assigned to the user.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the tasks assigned *to* this user.
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Route notification for FCM (Firebase Cloud Messaging).
     *
     * @return string|null
     */
    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Check if user has a specific role.
     *
     * @param string|array $role
     * @return bool
     */
    public function hasRole(string|array $role): bool
    {
        if (!$this->role) {
            return false;
        }

        $roleName = $this->role->role_name;

        return is_array($role)
            ? in_array($roleName, $role)
            : $roleName === $role;
    }

    /**
     * Check if user has a specific permission through their roles.
     *
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission(string $permissionCode): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->permissions->contains('code', $permissionCode);
    }

    /**
     * Get the company the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
