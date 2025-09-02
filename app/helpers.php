<?php

use App\Enums\TaskStatus;

if (!defined('DS')) {
    define('DS', '/');
}

if (!function_exists('status_label')) {
    /**
     * Get the translated label for a TaskStatus enum value.
     *
     * @param string|\App\Enums\TaskStatus $status
     * @return string
     */
    function status_label(string|TaskStatus $status): string
    {
        if (is_string($status)) {
            $status = TaskStatus::tryFrom($status);
        }

        return $status ? TaskStatus::label($status) : 'Desconocido';
    }
}
