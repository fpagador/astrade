<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
/**
 * Class NotificationPreference
 *
 * @property int $id
 * @property int $user_id
 * @property bool $visual_enabled
 * @property bool $audio_enabled
 * @property bool $push_enabled
 * @property string|null $time_window_start
 * @property string|null $time_window_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 */
class NotificationPreference extends Model
{
    use HasFactory;

    protected $table = 'notification_preferences';

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'visual_enabled',
        'audio_enabled',
        'push_enabled',
        'time_window_start',
        'time_window_end',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'visual_enabled' => 'boolean',
        'audio_enabled' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    /**
     * The user this preference belongs to.
     *
     * @return BelongsTo<User, NotificationPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
