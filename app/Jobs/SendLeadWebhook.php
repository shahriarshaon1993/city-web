<?php

namespace App\Jobs;

use App\Actions\Webhooks\DeliverWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * FR-LEAD-012: the queued webhook POST with 3 retries at 1, 5 and 15 minutes. The job only decides
 * when to try again; sending is `DeliverWebhook`. It releases itself instead of throwing, so an
 * endpoint that stays down is visible in the delivery log, not as a pile of failed jobs.
 */
class SendLeadWebhook implements ShouldQueue
{
    use Queueable;

    /** Minutes to wait after the 1st, 2nd and 3rd failed attempt. */
    public const array RETRY_MINUTES = [1, 5, 15];

    public int $tries = 4;

    public int $timeout = 30;

    public function __construct(public int $deliveryId, public string $body)
    {
        $this->onQueue('notifications');
    }

    public function handle(DeliverWebhook $deliver): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        if ($deliver->handle($delivery, $this->body)) {
            return;
        }

        $wait = self::RETRY_MINUTES[$this->attempts() - 1] ?? null;

        if ($wait !== null) {
            $this->release($wait * 60);
        }
    }
}
