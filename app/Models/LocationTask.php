<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationTask extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'location_tasks';

    /** @var array<int, string> */
    protected $fillable = [
        'task_id',
        'location_id',
    ];

    /**
     * Get the task that owns this location link.
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the location that is linked to this task.
     *
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

}
