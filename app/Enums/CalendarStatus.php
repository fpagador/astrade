<?php

namespace App\Enums;

enum CalendarStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

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
     * @param CalendarStatus $status
     * @return string
     */
    public static function label(self $status): string
    {
        return match ($status) {
            self::DRAFT => 'Borrador',
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
        };
    }
}
