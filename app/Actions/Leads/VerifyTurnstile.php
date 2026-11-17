<?php

namespace App\Actions\Leads;

use App\Support\Leads\TurnstileResult;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * FR-LEAD-003a: verifies a Cloudflare Turnstile token on the server (3 s timeout). No secret in
 * Settings → Forms means the check is off. A missing token (script blocked or slow) or an
 * unreachable Cloudflare accepts the submission but flags it for review.
 */
final class VerifyTurnstile
{
    public const string ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const int TIMEOUT_SECONDS = 3;

    public function handle(?string $token, ?string $ip): TurnstileResult
    {
        $secret = Settings::secret('forms', 'turnstile_secret');

        if ($secret === null) {
            return TurnstileResult::Skipped;
        }

        if ($token === null) {
            return TurnstileResult::Missing;
        }

        try {
            $response = Http::asForm()->timeout(self::TIMEOUT_SECONDS)->post(self::ENDPOINT, array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (Throwable) {
            return TurnstileResult::Unreachable;
        }

        if ($response->serverError()) {
            return TurnstileResult::Unreachable;
        }

        return $response->json('success') === true ? TurnstileResult::Passed : TurnstileResult::Failed;
    }

    /**
     * Like `handle()`, but a token Cloudflare rejects is a validation error on `turnstile`
     * (the form shows it beside the widget and keeps the input).
     *
     * @throws ValidationException
     */
    public function handleOrFail(?string $token, ?string $ip): TurnstileResult
    {
        $result = $this->handle($token, $ip);

        if ($result === TurnstileResult::Failed) {
            throw ValidationException::withMessages([
                'turnstile' => 'We could not confirm you are not a robot. Please try again.',
            ]);
        }

        return $result;
    }
}
