<?php

namespace App\Support\Forms;

/**
 * FR-LEAD-002 phone rules. Bangladesh mobile `^(?:\+?88)?01[3-9]\d{8}$`
 * becomes `+8801XXXXXXXXX`; any other country must give E.164
 * `^\+[1-9]\d{7,14}$`, either typed with its `+` or as a national number that
 * gets the selected country's dial code.
 */
final class PhoneNumber
{
    /**
     * For files and searches that carry no country choice: a Bangladesh mobile in any of its usual
     * spellings, or any number typed with its `+country code` (E.164). Facebook's `p:` prefix is ignored.
     */
    public static function fromAnyCountry(string $input): ?string
    {
        $input = preg_replace('/^\s*p:/i', '', $input) ?? $input;

        if (trim($input) === '') {
            return null;
        }

        $local = self::normalize($input, Countries::DEFAULT);

        if ($local !== null || ! str_contains($input, '+')) {
            return $local;
        }

        foreach (Countries::codes() as $code) {
            if ($code !== Countries::DEFAULT) {
                return self::normalize($input, $code);
            }
        }

        return null;
    }

    public static function normalize(string $input, string $country = Countries::DEFAULT): ?string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $input) ?? '';

        if ($country === Countries::DEFAULT) {
            return preg_match('/^(?:\+?88)?(01[3-9]\d{8})$/', $cleaned, $match) === 1
                ? '+88'.$match[1]
                : null;
        }

        $dial = Countries::dial($country);

        if ($dial === null) {
            return null;
        }

        $e164 = str_starts_with($cleaned, '+')
            ? $cleaned
            : '+'.$dial.ltrim($cleaned, '0');

        return preg_match('/^\+[1-9]\d{7,14}$/', $e164) === 1 ? $e164 : null;
    }
}
