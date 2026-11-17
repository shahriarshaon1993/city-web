<?php

namespace App\Support\Leads;

use App\Support\Consent\ConsentCookie;
use App\Support\Settings\Settings;
use Illuminate\Http\Request;

/**
 * What a submission carries besides the form's fields (FR-LEAD-001): network details, the GA client
 * id (only with analytics consent, NFR-PRIV-01) and the Turnstile token (FR-LEAD-003). Built from
 * the request in the controller so the Actions never touch it.
 */
final readonly class LeadContext
{
    public function __construct(
        public ?string $ip,
        public ?string $userAgent,
        public ?string $gaClientId,
        public ?string $turnstileToken,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $consent = ConsentCookie::read($request, (int) Settings::get('legal', 'consent_version', 1));
        $token = $request->input('cf-turnstile-response');

        return new self(
            ip: $request->ip(),
            userAgent: self::clip($request->userAgent(), 255),
            gaClientId: ($consent['analytics'] ?? false) ? self::gaClientId($request->cookie('_ga')) : null,
            turnstileToken: is_string($token) && $token !== '' ? $token : null,
        );
    }

    /** `GA1.1.123456789.1700000000` → `123456789.1700000000`. */
    private static function gaClientId(mixed $cookie): ?string
    {
        if (! is_string($cookie) || preg_match('/^GA\d\.\d+\.(\d+\.\d+)$/', $cookie, $match) !== 1) {
            return null;
        }

        return $match[1];
    }

    private static function clip(?string $value, int $length): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, $length);
    }
}
