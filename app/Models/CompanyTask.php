<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTask extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'company_tasks';

    /** @var array<int, string> */
    protected $fillable = [
        'task_id',
        'company_id',
    ];

    /**
     * Get the task that owns this company link.
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the company that is linked to this task.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

}
