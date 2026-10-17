<?php

namespace App\Domain\Profile\Enums;

enum ProfileAvatar: string
{
    case Amethyst = 'amethyst';
    case Bloom = 'bloom';
    case Cedar = 'cedar';
    case Clementine = 'clementine';
    case Dune = 'dune';
    case Fern = 'fern';
    case Iris = 'iris';
    case Juniper = 'juniper';
    case Luna = 'luna';
    case Olive = 'olive';
    case Sienna = 'sienna';
    case Sky = 'sky';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
