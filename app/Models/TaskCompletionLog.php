<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property int|null $subtask_id
 * @property Carbon $completed_at
 * @property-read User $user
 * @property-read Task $task
 * @property-read Subtask|null $subtask
 */
class TaskCompletionLog extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['user_id', 'task_id', 'subtask_id', 'completed_at'];

    /** @var array<int, string> */
    protected $casts = ['completed_at' => 'datetime'];

    /**
     * Get the user who completed the task or subtask.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task this log entry belongs to.
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the subtask this log entry belongs to, if any.
     *
     * @return BelongsTo
     */
    public function subtask(): BelongsTo
    {
        return $this->belongsTo(Subtask::class);
    }
}
