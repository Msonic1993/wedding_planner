<?php

namespace App\Domain\Auth\Enums;

enum UserRole: string
{
    case Bride = 'bride';
    case Groom = 'groom';
    case Vendor = 'vendor';
    case Admin = 'admin';

    /** @return list<string> */
    public static function registerableValues(): array
    {
        return [self::Bride->value, self::Groom->value, self::Vendor->value];
    }
}
