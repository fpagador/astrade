<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Location
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<Task> $tasks
 */
class Location extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['name', 'address', 'description'];

    /**
     * The tasks associated with this location.
     *
     * @return BelongsToMany<Task>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'location_tasks','location_id','task_id');
    }

    /**
     * Boot the model and handle model events.
     *
     * Automatically detach related tasks when a Location is deleted,
     * to clean up the pivot table `location_tasks`.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleting(function (Location $location): void {
            $location->tasks()->detach();
        });
    }

}
