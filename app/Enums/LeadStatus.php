<?php

namespace App\Enums;

/**
 * FR-LEAD-006 pipeline. `spam` and `needs_review` are set by the spam rules (FR-LEAD-003);
 * `won` and `lost` close a lead.
 */
enum LeadStatus: string
{
    case New = 'new';
    case NeedsReview = 'needs_review';
    case Contacted = 'contacted';
    case SiteVisitScheduled = 'site_visit_scheduled';
    case SiteVisitDone = 'site_visit_done';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::NeedsReview => 'Needs review',
            self::Contacted => 'Contacted',
            self::SiteVisitScheduled => 'Site visit scheduled',
            self::SiteVisitDone => 'Site visit done',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Spam => 'Spam',
        };
    }

    /** Won and lost need a reason (FR-LEAD-006). */
    public function needsReason(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }

    /**
     * Statuses that no longer count as open work (round-robin by least open leads, FR-LEAD-007).
     *
     * @return list<string>
     */
    public static function closedValues(): array
    {
        return [self::Won->value, self::Lost->value, self::Spam->value];
    }
}
