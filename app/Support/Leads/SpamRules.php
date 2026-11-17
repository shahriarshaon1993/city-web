<?php

namespace App\Support\Leads;

/**
 * FR-LEAD-003d content rules: a message with more than three links, or written only in scripts
 * other than Bengali and Latin, is stored as `spam` instead of being rejected, so nothing a real
 * person wrote is ever lost silently.
 */
final class SpamRules
{
    public const int MAX_URLS = 3;

    /**
     * The reason a message looks like spam, or null.
     */
    public static function reason(?string $message): ?string
    {
        if ($message === null || trim($message) === '') {
            return null;
        }

        if (preg_match_all('~(?:https?://|www\.)\S+~i', $message) > self::MAX_URLS) {
            return 'too_many_links';
        }

        $hasLetters = preg_match('/\p{L}/u', $message) === 1;
        $hasKnownScript = preg_match('/[\p{Latin}\p{Bengali}]/u', $message) === 1;

        return $hasLetters && ! $hasKnownScript ? 'foreign_script' : null;
    }
}
