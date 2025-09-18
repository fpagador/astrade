<?php

namespace App\Services;

use App\Errors\ErrorCodes;
use App\Repositories\TaskCompletionLogRepository;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskCompletionLogService
{

    /**
     * TaskCompletionLogService Constructor
     *
     * @param TaskCompletionLogRepository $repository
     */
    public function __construct(
        protected TaskCompletionLogRepository $repository
    ) {}

    //================================ API ======================================

    /**
     * Retrieve task completion logs for a user.
     *
     * @param int $userId
     * @return Collection
     * @throws BusinessRuleException
     */
    public function getUserTaskCompletions(int $userId): Collection
    {
        $logs = $this->repository->getByUserId($userId);

        if ($logs->isEmpty()) {
            throw new BusinessRuleException(
                'No task completion logs found for this user',
                404,
                ErrorCodes::TASK_COMPLETION_LOGS_NOT_FOUND,
                'TASKS'
            );
        }

        return $logs;
    }

    //================================ WEB ======================================

    /**
     * Get paginated task completion logs with filters and sorting.
     *
     * @param array $filters
     * @param string $sort
     * @param string $direction
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedLogs(
        array $filters = [],
        string $sort = 'completed_at',
        string $direction = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $sort, $direction, $perPage);
    }
}
