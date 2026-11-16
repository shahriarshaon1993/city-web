<?php

namespace App\Enums;

/** FR-PROJ project status (`projects.lifecycle`); the publish workflow is `PublishStatus`. */
enum Lifecycle: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Ready = 'ready';
    case HandedOver = 'handed_over';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Ongoing => 'Ongoing',
            self::Ready => 'Ready',
            self::HandedOver => 'Handed over',
        };
    }

    /** Ready and handed-over projects have finished construction. */
    public function isComplete(): bool
    {
        return $this === self::Ready || $this === self::HandedOver;
    }
}
