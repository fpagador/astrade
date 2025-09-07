<?php
namespace App\Errors;

/**
* Centralized catalog of business-specific error codes.
*
* Each constant represents a machine-readable error code
* that the API can return in problem+json responses.
*
* Grouped by logical category (SUBTASKS, TASKS, CALENDAR, LOGS).
*/
class ErrorCodes
{
// ===== SUBTASKS =====
public const SUBTASK_INVALID_STATUS      = 'SUBTASK_INVALID_STATUS';
public const SUBTASK_PERMISSION_DENIED   = 'SUBTASK_PERMISSION_DENIED';

// ===== TASKS =====
public const TASK_NO_COMPANY                = 'TASK_NO_COMPANY';

// ===== CALENDAR =====
public const VACATION_NOT_FOUND = 'VACATION_NOT_FOUND';

// ===== TASK COMPLETION LOGS =====
public const TASK_COMPLETION_LOGS_NOT_FOUND = 'TASK_COMPLETION_LOGS_NOT_FOUND';
}
