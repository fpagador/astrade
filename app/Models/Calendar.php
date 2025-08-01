<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class Calendar
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property string|null $day_type
 * @property string|null $reason
 * @property string|null $type
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 */
class Calendar extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['user_id', 'date', 'day_type', 'reason', 'type', 'description'];

    /** @var array<string, string> */
    protected $casts = ['date' => 'date'];

    /**
     * Get the user who owns this calendar entry.
     *
     * @return BelongsTo<User, Calendar>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
