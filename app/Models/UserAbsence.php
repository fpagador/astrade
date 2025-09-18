<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use App\Models\User;

/**
 * Class UserAbsence
 *
 * Represents a vacation day assigned to a specific user.
 * Vacation days are independent from work calendars templates.
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 */
class UserAbsence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'description',
        'type'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user that owns this vacation or legal absence day.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
