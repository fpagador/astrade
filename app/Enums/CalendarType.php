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
     * @param TaskStatus $status
     * @return string
     */
    public static function label(self $status): string
    {
        return match ($status) {
            self::VACATION => 'Vacaciones',
            self::LEGAL_ABSENCE => 'Ausencias legales',
            self::HOLIDAY => 'Festivos',
        };
    }
}
