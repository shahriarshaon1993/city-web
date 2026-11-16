<?php

namespace App\Enums;

/** DM-NEAR `category`. */
enum NearbyCategory: string
{
    case School = 'school';
    case Hospital = 'hospital';
    case Mall = 'mall';
    case Mosque = 'mosque';
    case Transport = 'transport';
    case Park = 'park';
    case Office = 'office';
    case Airport = 'airport';

    public function label(): string
    {
        return match ($this) {
            self::School => 'School',
            self::Hospital => 'Hospital',
            self::Mall => 'Shopping',
            self::Mosque => 'Mosque',
            self::Transport => 'Transport',
            self::Park => 'Park',
            self::Office => 'Office',
            self::Airport => 'Airport',
        };
    }
}
