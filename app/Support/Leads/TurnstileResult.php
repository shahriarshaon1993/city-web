<?php

namespace App\Support\Leads;

/**
 * Outcome of the Turnstile check (FR-LEAD-003a). `Missing` and `Unreachable` accept the submission
 * and flag it `needs_review`; `Failed` is refused; `Skipped` means no secret is configured.
 */
enum TurnstileResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Unreachable = 'unreachable';
    case Missing = 'missing';
    case Skipped = 'skipped';

    public function needsReview(): bool
    {
        return $this === self::Unreachable || $this === self::Missing;
    }
}
