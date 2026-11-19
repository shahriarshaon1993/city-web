<?php

namespace App\Enums;

/** FR-CAR-005 pipeline of a job application. */
enum ApplicationStatus: string implements Labelled
{
    case New = 'new';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Rejected = 'rejected';
    case Hired = 'hired';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Rejected => 'Rejected',
            self::Hired => 'Hired',
        };
    }
}
