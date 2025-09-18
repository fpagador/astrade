<?php

namespace App\Repositories;

use App\Models\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Repository class for handling database interactions related to logs.
 */
class LogRepository
{
    /**
     * Get filtered logs query builder.
     *
     * @param array $filters
     * @return Builder
     */
    public function query(array $filters = []): Builder
    {
        $query = Log::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['message'])) {
            $query->where('message', 'like', '%' . $filters['message'] . '%');
        }

        return $query;
    }

    /**
     * Get paginated logs with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all distinct log levels for dropdowns or filtering.
     *
     * @return Collection
     */
    public function getDistinctLevels(): Collection
    {
        return Log::select('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level');
    }
}
