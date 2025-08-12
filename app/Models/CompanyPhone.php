<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * App\Models\CompanyPhone
 *
 * @property int $id
 * @property int $company_id
 * @property string $phone_number
 * @property string|null $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Company $company
 *
 */
class CompanyPhone extends Model
{
    protected $fillable = [
        'phone_number',
        'name',
    ];

    /**
     * Get the company that owns the phone.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

