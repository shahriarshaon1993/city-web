<?php

namespace App\Support\Leads;

use App\Support\Settings\Settings;
use Spatie\Honeypot\Honeypot;

/**
 * What the browser needs for FR-LEAD-003: the Turnstile site key (public) and the honeypot field
 * names with the encrypted "not before" time. Part of the `forms` shared prop, sent once per visit;
 * a form can only be sent `honeypot_min_seconds` after the page that showed it was built.
 */
final class SpamProtectionProps
{
    /**
     * @return array{turnstile_site_key: string|null, honeypot: array{enabled: bool, name: string, valid_from_name: string, valid_from: string}}
     */
    public static function shared(): array
    {
        $honeypot = new Honeypot([
            ...config('honeypot'),
            'amount_of_seconds' => (int) Settings::get('forms', 'honeypot_min_seconds', 3),
        ]);

        $key = Settings::get('forms', 'turnstile_site_key');

        return [
            'turnstile_site_key' => is_string($key) && $key !== '' ? $key : null,
            'honeypot' => [
                'enabled' => $honeypot->enabled(),
                'name' => $honeypot->nameFieldName(),
                'valid_from_name' => $honeypot->validFromFieldName(),
                'valid_from' => $honeypot->encryptedValidFrom(),
            ],
        ];
    }
}
