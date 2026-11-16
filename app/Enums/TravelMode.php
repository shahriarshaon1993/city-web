<?php

namespace App\Enums;

/** DM-NEAR `mode`. */
enum TravelMode: string
{
    case Drive = 'drive';
    case Walk = 'walk';

    public function label(): string
    {
        return $this->value;
    }
}
