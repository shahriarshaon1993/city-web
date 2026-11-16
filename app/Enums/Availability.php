<?php

namespace App\Enums;

/** DM-UNIT `availability`. */
enum Availability: string
{
    case Available = 'available';
    case Limited = 'limited';
    case SoldOut = 'sold_out';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Limited => 'Limited',
            self::SoldOut => 'Sold out',
        };
    }
}
