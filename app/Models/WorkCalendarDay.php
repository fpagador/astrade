<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\WorkCalendarTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class WorkCalendarDay
 *
 * Represents a specific day within a work calendars template.
 * This can be marked as holiday, weekend, or workday.
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $template_id
 * @property Carbon $date
 * @property string $day_type
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read WorkCalendarTemplate $template
 */
class WorkCalendarDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'date',
        'day_type',
        'description'
    ];

    /**
     * Get the work calendars template that owns this day.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkCalendarTemplate::class, 'template_id');
    }
}
