<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    /**
     * HTTP status code for the exception
     *
     * @var int
     */
    protected int $status;

    /**
     * Internal business-specific error code
     *
     * @var string|null
     */
    protected ?string $errorCode;

    /**
     * Logical error category (e.g. SUBTASKS, TASKS, CALENDAR)
     *
     * @var string|null
     */
    protected ?string $category;

    /**
     * BusinessRuleException constructor.
     *
     * @param string      $message
     * @param int         $status
     * @param string|null $errorCode
     * @param string|null $category
     */
    public function __construct(
        string $message,
        int $status = 400,
        ?string $errorCode = null,
        ?string $category = null
    ) {
        parent::__construct($message, $status);
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->category = $category;
    }

    /**
     * Get the corresponding HTTP status code
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the business-specific error code.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get the logical error category.
     *
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }
}
