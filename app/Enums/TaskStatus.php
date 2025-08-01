<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    /**
     * Get all values as array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get the translated label of a status.
     *
     * @param TaskStatus $status
     * @return string
     */
    public static function label(self $status): string
    {
        return match ($status) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completada',
        };
    }
}
