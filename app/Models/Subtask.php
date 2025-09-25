<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property string|null $description
 * @property string|null $note
 * @property int|null $order
 * @property string|null $status
 * @property string|null $pictogram_path
 * @property-read Task $task
 */
class Subtask extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['task_id', 'title', 'description', 'note', 'order', 'status', 'pictogram_path'];

    /**
     * Get the parent task of this subtask.
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected static function booted()
    {
        static::creating(function ($subtask) {
            if (empty($subtask->external_id)) {
                $subtask->external_id = (string) Str::uuid();
            }
        });
    }
}
