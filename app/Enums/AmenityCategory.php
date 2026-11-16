<?php

namespace App\Enums;

/** DM-AMEN `category`. */
enum AmenityCategory: string
{
    case Building = 'building';
    case Security = 'security';
    case Lifestyle = 'lifestyle';
    case Utility = 'utility';

    public function label(): string
    {
        return match ($this) {
            self::Building => 'Building',
            self::Security => 'Safety & security',
            self::Lifestyle => 'Lifestyle',
            self::Utility => 'Utilities',
        };
    }
}
