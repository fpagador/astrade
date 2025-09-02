<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    /**
     * @var int
     */
    protected int $status;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $status
     */
    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message, $status);
        $this->status = $status;
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
}
