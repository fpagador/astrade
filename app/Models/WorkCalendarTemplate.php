<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class WorkCalendarTemplate
 *
 * Represents a yearly work calendars template that can be assigned to multiple users.
 * A template contains a collection of specific days (holidays, weekends, workdays).
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property int $year
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|\App\Models\WorkCalendarDay[] $days
 * @property-read Collection|\App\Models\User[] $users
 */
class WorkCalendarTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'status'
    ];

    /**
     * Get all days (holidays, weekends, workdays) for this template.
     *
     * @return HasMany
     */
    public function days(): HasMany
    {
        return $this->hasMany(WorkCalendarDay::class, 'template_id');
    }

    /**
     * Get all users assigned to this template.
     *
     * @return HasMany
     */
    public function users(): hasMany
    {
        return $this->hasMany(User::class, 'work_calendar_template_id');
    }
}
