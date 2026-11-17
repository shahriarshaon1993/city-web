<?php

namespace App\Listeners;

use App\Actions\Leads\SendLeadAutoresponder;
use App\Actions\Notifications\NotifyLeadAssigned;
use App\Actions\Notifications\NotifyNewLead;
use App\Enums\LeadChange;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use Throwable;

/**
 * FR-LEAD-008: dashboard notifications, the optional team email and the optional autoresponder.
 * Runs inside the request that stored the lead, so a failure is reported and never reaches the
 * visitor (their lead is already saved). Email itself is queued.
 */
final class NotifyAboutLead
{
    public function __construct(
        private readonly NotifyNewLead $notifyNewLead,
        private readonly NotifyLeadAssigned $notifyLeadAssigned,
        private readonly SendLeadAutoresponder $autoresponder,
    ) {}

    public function onCreated(LeadCreated $event): void
    {
        try {
            $this->notifyNewLead->handle($event->lead, $event->actor);
            $this->autoresponder->handle($event->lead);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function onUpdated(LeadUpdated $event): void
    {
        if ($event->change !== LeadChange::Assigned) {
            return;
        }

        try {
            $this->notifyLeadAssigned->handle($event->lead, $event->actor);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
