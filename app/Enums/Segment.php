<?php

namespace App\Enums;

/** FR-PROJ project type (`projects.segment`). */
enum Segment: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case MixedUse = 'mixed_use';
    case Land = 'land';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::MixedUse => 'Mixed-use',
            self::Land => 'Land',
        };
    }
}
