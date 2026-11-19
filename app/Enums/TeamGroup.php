<?php

namespace App\Enums;

/** FR-SOC-004 team groups. */
enum TeamGroup: string implements Labelled
{
    case Leadership = 'leadership';
    case Management = 'management';
    case Team = 'team';

    public function label(): string
    {
        return match ($this) {
            self::Leadership => 'Leadership',
            self::Management => 'Management',
            self::Team => 'Team',
        };
    }
}
