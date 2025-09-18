<?php

namespace App\Services;

use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service class for business logic related to logs.
 */
class LogService
{

    /**
     * LogService constructor.
     *
     * @param LogRepository $logRepository
     */
    public function __construct(
        protected LogRepository $logRepository
    ) {}

    /**
     * Get paginated logs with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->logRepository->paginate($filters, $perPage);
    }

    /**
     * Get all unique log levels.
     *
     * @return Collection
     */
    public function getLogLevels(): Collection
    {
        return $this->logRepository->getDistinctLevels();
    }
}
