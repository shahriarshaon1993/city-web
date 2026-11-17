<?php

namespace App\Enums;

/**
 * FR-LEAD-006 pipeline. `spam` and `needs_review` are set by the spam rules (FR-LEAD-003);
 * `won` and `lost` close a lead.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case SiteVisitScheduled = 'site_visit_scheduled';
    case SiteVisitDone = 'site_visit_done';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case Spam = 'spam';
    case NeedsReview = 'needs_review';

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
