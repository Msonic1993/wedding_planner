<?php

namespace App\Domain\Vendor\Enums;

enum VendorCategory: string
{
    case Photographer = 'photographer';
    case Videographer = 'videographer';
    case Dj = 'dj';
    case Band = 'band';
    case Venue = 'venue';
    case Florist = 'florist';
    case Decorations = 'decorations';
    case Cake = 'cake';
    case Car = 'car';
    case Makeup = 'makeup';
    case Hairdresser = 'hairdresser';
    case WeddingPlanner = 'wedding_planner';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
