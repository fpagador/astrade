<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';

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
     * Get the translated label of a roles.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Preparador',
            self::USER => 'Usuario móvil',
        };
    }

    public static function labels(): array
    {
        return array_map(
            fn($case) => $case->label(),
            self::cases()
        );
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($role) {
            return [$role->value => $role->label()];
        })->toArray();
    }
}
