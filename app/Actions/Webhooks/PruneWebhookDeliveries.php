<?php

namespace App\Actions\Webhooks;

use App\Models\WebhookDelivery;

/** FR-LEAD-012 / DM-WEBH: the delivery log is kept for 30 days. Returns how many rows were removed. */
final class PruneWebhookDeliveries
{
    public function handle(): int
    {
        return WebhookDelivery::query()
            ->where('created_at', '<', now()->subDays(WebhookDelivery::RETENTION_DAYS))
            ->delete();
    }
}
