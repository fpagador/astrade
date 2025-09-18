<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Company
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<Task> $tasks
 */
class Company extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['name', 'address', 'description'];

    /**
     * Boot the model and handle model events.
     *
     * Automatically detach related tasks when a Company is deleted,
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleting(function (Company $company): void {
            $company->tasks()->detach();
        });
    }

    /**
     * Get the users that belong to the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the phones that belong to the company.
     */
    public function phones()
    {
        return $this->hasMany(CompanyPhone::class);
    }

}
