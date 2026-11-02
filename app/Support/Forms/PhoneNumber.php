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
