<?php

namespace App\Enums;

enum CalendarColor: string
{
    case VACATION = 'bg-green-500';
    case LEGAL_ABSENCE = 'bg-orange-400';
    case HOLIDAY = 'bg-yellow-200';
    case WEEKEND = 'bg-gray-300';
    case WORKING = 'bg-gray-100';

    /**
     * Get all values as array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            'VACATION' => [
                'class' => self::VACATION->value,
                'hex' => '#22c55e',
            ],
            'LEGAL_ABSENCE' => [
                'class' => self::LEGAL_ABSENCE->value,
                'hex' => '#fb923c',
            ],
            'HOLIDAY' => [
                'class' => self::HOLIDAY->value,
                'hex' => '#fef08a',
            ],
            'WEEKEND' => [
                'class' => self::WEEKEND->value,
                'hex' => '#d1d5db',
            ],
            'WORKING' => [
                'class' => self::WORKING->value,
                'hex' => '#f3f4f6',
            ],
        ];
    }
}
