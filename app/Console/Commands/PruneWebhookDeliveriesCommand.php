<?php

namespace App\Console\Commands;

use App\Actions\Webhooks\PruneWebhookDeliveries;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('webhooks:prune')]
#[Description('Delete webhook deliveries older than 30 days (FR-LEAD-012)')]
class PruneWebhookDeliveriesCommand extends Command
{
    public function handle(PruneWebhookDeliveries $prune): int
    {
        $this->info("Removed {$prune->handle()} deliveries.");

        return self::SUCCESS;
    }
}
