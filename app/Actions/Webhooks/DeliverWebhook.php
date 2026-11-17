<?php

namespace App\Actions\Webhooks;

use App\Models\WebhookDelivery;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * FR-LEAD-012: one attempt to POST a queued webhook body with `X-Signature: sha256=HMAC(body, secret)`.
 * The URL and secret are read now, so a change in Settings applies to retries. Returns true when the
 * receiver answered 2xx; false means "try again later" (a timeout, a 5xx, a refused connection, a 4xx).
 * A webhook that was switched off, or has no secret, is recorded and not retried (also true).
 * Redirects are not followed, so a receiver cannot bounce the signed body elsewhere.
 */
final class DeliverWebhook
{
    public function handle(WebhookDelivery $delivery, string $body): bool
    {
        $delivery->increment('attempts');

        $url = (string) Settings::get('webhook', 'url', '');
        $secret = Settings::secret('webhook', 'secret');

        if (! (bool) Settings::get('webhook', 'enabled', false) || $url === '') {
            return $this->giveUp($delivery, 'The webhook is switched off.');
        }

        if (blank($secret)) {
            return $this->giveUp($delivery, 'No signing secret is set (Settings → Webhook), so nothing was sent.');
        }

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withoutRedirecting()
                ->withHeaders([
                    'X-Signature' => 'sha256='.hash_hmac('sha256', $body, $secret),
                    'X-Gemcon-Event' => $delivery->event,
                    'X-Gemcon-Delivery' => (string) $delivery->id,
                    'User-Agent' => 'GemconCity-Webhook/1.0',
                ])
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status_code' => null,
                'response_excerpt' => Str::limit($exception->getMessage(), 500, ''),
            ])->save();

            return false;
        }

        $ok = $response->successful();

        $delivery->forceFill([
            'status_code' => $response->status(),
            'response_excerpt' => Str::limit(trim($response->body()), 500, ''),
            'delivered_at' => $ok ? now() : null,
        ])->save();

        return $ok;
    }

    private function giveUp(WebhookDelivery $delivery, string $reason): bool
    {
        // Status 0 = nothing was sent; the delivery log shows it as skipped.
        $delivery->forceFill(['status_code' => 0, 'response_excerpt' => $reason])->save();

        return true;
    }
}
