<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case MANAGEMENT = 'management';
    case MOBILE = 'mobile';

    /**
     * Get all values as array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
