<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\ResetPasswordNotification;

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
 * @property bool $can_be_called
 * @property int|null $role_id
 * @property int|null $company_id
 * @property int|null $work_calendar_template_id
 * @property string|null $phone
 * @property-read Role|null $role
 * @property-read Company|null $company
 * @property-read WorkCalendarTemplate|null $workCalendarTemplate
 * @property-read Collection<Task> $tasks
 * @property-read Collection<Task> $assignedTasks
 * @property-read Collection<UserAbsence> $vacations
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'name', 'surname', 'dni', 'email', 'username', 'password', 'photo',
        'work_schedule', 'contract_type', 'contract_start_date',
        'notification_type', 'can_receive_notifications', 'role_id', 'phone',
        'company_id', 'work_calendar_template_id', 'can_be_called'
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

    /**
     * Get the work calendars templates assigned to the user.
     *
     * @return BelongsTo
     */
    public function workCalendarTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkCalendarTemplate::class, 'work_calendar_template_id');
    }

    /**
     * Get all vacation days for the user.
     *
     * @return HasMany
     */
    public function absences(): HasMany
    {
        return $this->hasMany(UserAbsence::class);
    }

    /**
     * Send password reset notification
     *
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
