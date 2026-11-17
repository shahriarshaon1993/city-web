<?php

namespace App\Listeners;

use App\Actions\Webhooks\QueueLeadWebhook;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use Throwable;

/** FR-LEAD-012: every lead create / update becomes one queued, signed POST when the webhook is on. */
final class ForwardLeadToWebhook
{
    public function __construct(private readonly QueueLeadWebhook $queueWebhook) {}

    public function onCreated(LeadCreated $event): void
    {
        $this->queue($event, 'lead.created', null);
    }

    public function onUpdated(LeadUpdated $event): void
    {
        $this->queue($event, 'lead.updated', $event->change->value);
    }

    private function queue(LeadCreated|LeadUpdated $event, string $name, ?string $change): void
    {
        try {
            $this->queueWebhook->handle($event->lead, $name, $change);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
