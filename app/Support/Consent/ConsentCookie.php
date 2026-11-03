<?php

namespace App\Support\Consent;

use Illuminate\Http\Request;

/**
 * The visitor's cookie choice (CMP-COOKIE-01, NFR-PRIV-01). The browser
 * writes `gc_consent` (JSON, 12 months, plain text: exempt from cookie
 * encryption in bootstrap/app.php); the server only reads it, so a returning
 * visitor's banner is not drawn again and `ui.consent` is known on first
 * paint. A choice stored under an older `consent_version` counts as none,
 * which re-prompts everyone after a bump (Settings → Legal & cookies).
 *
 * @phpstan-type Consent array{version: int, analytics: bool, marketing: bool}
 */
final class ConsentCookie
{
    public const string NAME = 'gc_consent';

    /**
     * @return Consent|null
     */
    public static function read(Request $request, int $currentVersion): ?array
    {
        $raw = $request->cookie(self::NAME);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        if (! is_array($data) || ($data['version'] ?? null) !== $currentVersion) {
            return null;
        }

        return [
            'version' => $currentVersion,
            'analytics' => ($data['analytics'] ?? false) === true,
            'marketing' => ($data['marketing'] ?? false) === true,
        ];
    }
}
