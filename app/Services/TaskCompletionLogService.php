<?php

namespace App\Services;

use App\Errors\ErrorCodes;
use App\Repositories\TaskCompletionLogRepository;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Collection;

class TaskCompletionLogService
{
    protected TaskCompletionLogRepository $repository;

    public function __construct(TaskCompletionLogRepository $repository)
    {
        $this->repository = $repository;
    }

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
}
