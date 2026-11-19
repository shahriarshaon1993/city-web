<?php

namespace App\Enums;

/**
 * FR-CAR-001 `status`. Only `open` jobs take applications; the scheduler closes an open job after its
 * deadline (FR-CAR-003).
 */
enum JobStatus: string implements Labelled
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }
}
