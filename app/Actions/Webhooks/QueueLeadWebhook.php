<?php

namespace App\Actions\Webhooks;

use App\Jobs\SendLeadWebhook;
use App\Models\Lead;
use App\Models\WebhookDelivery;
use App\Support\Notifications\LeadWebhookPayload;
use App\Support\Settings\Settings;

/**
 * FR-LEAD-012: when the webhook is on and has a URL, writes the delivery row and queues one signed
 * POST for a lead event (`lead.created`, `lead.updated`). The body is fixed here, so every retry
 * sends — and signs — exactly the same bytes. Returns null when the webhook is off.
 */
final class QueueLeadWebhook
{
    public function handle(Lead $lead, string $event, ?string $change = null): ?WebhookDelivery
    {
        if (! (bool) Settings::get('webhook', 'enabled', false) || ! filled(Settings::get('webhook', 'url'))) {
            return null;
        }

        $delivery = WebhookDelivery::create(['event' => $event, 'lead_id' => $lead->id]);

        $body = json_encode(
            LeadWebhookPayload::build($lead, $event, $change, $delivery->id),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        SendLeadWebhook::dispatch($delivery->id, $body);

        return $delivery;
    }
}
