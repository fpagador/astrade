<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property array $days_of_week
 * @property string $template_title
 * @property string|null $template_description
 * @property Carbon $template_scheduled_time
 * @property int $template_estimated_duration_minutes
 * @property-read User $user
 * @property-read Collection<Task> $tasks
 */
class RecurrentTask extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'days_of_week',
        'template_title',
        'template_description',
        'template_scheduled_time',
        'template_estimated_duration_minutes',
    ];

    /** @var array<int, string> */
    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'template_scheduled_time' => 'datetime:H:i',
    ];

    /**
     * Get the user that owns the recurrent task.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tasks generated from this recurrent task.
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
