<?php

namespace App\Enums;

enum NotificationType: string
{
    case NONE = 'none';
    case VISUAL = 'visual';
    case VISUAL_AUDIO = 'visual_audio';

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
            self::NONE => 'Ninguna',
            self::VISUAL => 'Visual',
            self::VISUAL_AUDIO => 'Visual y audio'
        };
    }
}
