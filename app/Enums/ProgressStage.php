<?php

namespace App\Enums;

/** DM-PROG `stage`. */
enum ProgressStage: string
{
    case Foundation = 'foundation';
    case Structure = 'structure';
    case Finishing = 'finishing';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::Foundation => 'Foundation',
            self::Structure => 'Structure',
            self::Finishing => 'Finishing',
            self::Complete => 'Complete',
        };
    }
}
