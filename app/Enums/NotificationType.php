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
     * @param NotificationType $notificationType
     * @return string
     */
    public static function label(self $notificationType): string
    {
        return match ($notificationType) {
            self::NONE => 'Ninguna',
            self::VISUAL => 'Visual',
            self::VISUAL_AUDIO => 'Visual y audio'
        };
    }
}
