<?php

namespace App\Enums;

enum ContractType: string
{
    case TEMPORARY = 'temporary';
    case PERMANENT = 'permanent';
    case FIXED_DISCONTINUOUS = 'fixed_discontinuous';

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
            self::TEMPORARY => 'Temporal',
            self::PERMANENT => 'Indefinido',
            self::FIXED_DISCONTINUOUS => 'Fijo discontinuo'
        };
    }
}
