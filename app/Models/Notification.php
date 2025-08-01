<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Notification
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $task_id
 * @property string $title
 * @property string $body
 * @property Carbon|null $scheduled_at
 * @property bool $delivered
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read Task|null $task
 */
class Notification extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['user_id', 'task_id', 'title', 'body', 'scheduled_at', 'delivered'];

    /** @var array<string, string> */
    protected $casts = ['scheduled_at' => 'datetime', 'delivered' => 'boolean'];

    /**
     * The user this notification belongs to.
     *
     * @return BelongsTo<User, Notification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The task related to this notification (if any).
     *
     * @return BelongsTo<Task, Notification>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

