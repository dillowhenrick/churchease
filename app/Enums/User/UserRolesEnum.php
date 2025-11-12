<?php

namespace App\Enums\User;

enum UserRolesEnum: string
{
    case SuperAdmin = 'Super Admin';
    case ChurchAdmin = 'Church Admin';

    public static function values(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases()
        );
    }
}
