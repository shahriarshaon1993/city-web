<?php

namespace App\Support\Social;

/**
 * Office phone numbers (FR-SOC-005) are stored as E.164 like every other number, but unlike a lead's
 * they may be landlines or hotlines (`096…`, `02…`): a leading 0 is read as a Bangladesh number, a
 * leading `+` or `88` as already international.
 */
final class OfficePhones
{
    public static function normalize(string $input): ?string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $input) ?? '';

        $e164 = match (true) {
            str_starts_with($cleaned, '+') => $cleaned,
            str_starts_with($cleaned, '88') => '+'.$cleaned,
            str_starts_with($cleaned, '0') => '+88'.$cleaned,
            default => '',
        };

        return preg_match('/^\+[1-9]\d{7,14}$/', $e164) === 1 ? $e164 : null;
    }
}
