<?php

namespace App\Enums;

enum CalendarType: string
{
    case VACATION = 'vacation';
    case LEGAL_ABSENCE = 'legal_absence';
    case HOLIDAY = 'holiday';

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
     * @param CalendarType $type
     * @return string
     */
    public static function label(self $type): string
    {
        return match ($type) {
            self::VACATION => 'Vacaciones',
            self::LEGAL_ABSENCE => 'Ausencias legales',
            self::HOLIDAY => 'Festivos',
        };
    }
}
