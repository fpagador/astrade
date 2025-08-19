<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $assigned_by
 * @property string $title
 * @property string|null $description
 * @property Carbon $scheduled_date
 * @property Carbon $scheduled_time
 * @property int|null $estimated_duration_minutes
 * @property string|null $pictogram_path
 * @property int|null $order
 * @property string|null $status
 * @property int|null $recurrent_task_id
 *
 * @property-read User $user
 * @property-read User $assignedBy
 * @property-read RecurrentTask|null $recurrentTask
 * @property-read Collection<Subtask> $subtasks
 * @property-read Collection<Notification> $notifications
 * @property-read Collection<Company> $companies
 */
class Task extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'assigned_by',
        'title',
        'description',
        'color',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration_minutes',
        'pictogram_path',
        'status',
        'recurrent_task_id'
    ];

    /** @var array<int, string> */
    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
    ];

    /**
     * Get the user assigned to the task.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who assigned this task.
     *
     * @return BelongsTo
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the subtasks of this task.
     *
     * @return HasMany
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    /**
     * Get the recurrent task this task belongs to.
     *
     * @return BelongsTo
     */
    public function recurrentTask(): BelongsTo
    {
        return $this->belongsTo(RecurrentTask::class, 'recurrent_task_id');
    }

    /**
     * Get the notifications related to this task.
     *
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the companies assigned to this task.
     *
     * @return BelongsToMany
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_tasks', 'task_id', 'company_id');
    }

    /**
     * Get the predefined color palette for tasks.
     *
     * These colors have been selected to be visually appealing and friendly for children with autism,
     * offering a variety of soft and bright hues that are easy to distinguish.
     *
     * @return array<string>
     */
    public static function getColors(): array
    {
        return [
            '#FFFFFF', // White
            '#FF6B6B', // Light Coral
            '#FFB347', // Pastel Orange
            '#FFD93D', // Mustard Yellow
            '#6BCB77', // Medium Sea Green
            '#4D96FF', // Cornflower Blue
            '#989EFA', // Light Periwinkle
            '#F593C4', // Orchid Pink
            '#00CED1', // Dark Turquoise
            '#ADFF2F', // Green Yellow
            '#FF8C00', // Dark Orange
            '#20B2AA', // Light Sea Green
            '#D6C2FC', // Lavender
            '#CFB0B0', // Pale Rose
            '#00BFFF', // Deep Sky Blue
            '#F4A460', // Sandy Brown
        ];
    }
}
