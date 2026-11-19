<?php

namespace App\Support\Careers;

/**
 * Why an application was flagged "Needs review" (FR-LEAD-003): the stored `review_reason` and the plain
 * sentence the admin shows.
 */
final class ReviewReasons
{
    /** @var array<string, string> */
    private const array LABELS = [
        'turnstile_missing' => 'The spam check did not run in the visitor’s browser (script blocked or slow).',
        'turnstile_unreachable' => 'The spam check service could not be reached when this was sent.',
        'too_many_links' => 'The cover letter has more than three links.',
        'foreign_script' => 'The cover letter is not written in Bengali or English letters.',
    ];

    public static function label(?string $reason): ?string
    {
        return $reason === null ? null : (self::LABELS[$reason] ?? 'Flagged by the spam rules.');
    }
}
