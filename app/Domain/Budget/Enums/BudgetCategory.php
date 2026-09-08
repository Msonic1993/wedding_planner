<?php

namespace App\Domain\Budget\Enums;

enum BudgetCategory: string
{
    case Venue = 'venue';
    case Catering = 'catering';
    case Photographer = 'photographer';
    case Videographer = 'videographer';
    case Dj = 'dj';
    case Band = 'band';
    case Decorations = 'decorations';
    case Florist = 'florist';
    case Cake = 'cake';
    case Attire = 'attire';
    case Rings = 'rings';
    case Transport = 'transport';
    case Alcohol = 'alcohol';
    case Invitations = 'invitations';
    case Makeup = 'makeup';
    case Hairdresser = 'hairdresser';
    case WeddingPlanner = 'wedding_planner';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
