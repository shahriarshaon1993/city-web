<?php

namespace App\Enums;

/** FR-LOC-001: the two levels of the location tree. */
enum LocationType: string
{
    case City = 'city';
    case Area = 'area';

    public function label(): string
    {
        return match ($this) {
            self::City => 'City',
            self::Area => 'Area',
        };
    }
}
