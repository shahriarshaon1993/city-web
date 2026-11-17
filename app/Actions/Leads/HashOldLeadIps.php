<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Support\Leads\IpHash;

/**
 * NFR-PRIV-04 / FR-LEAD-001: a lead keeps its raw IP for 90 days; after that the address is
 * replaced by its salted SHA-256 hash (still enough to spot repeat senders, no longer personal).
 * Soft-deleted leads are included.
 */
final class HashOldLeadIps
{
    /**
     * @return int Number of leads changed.
     */
    public function handle(): int
    {
        $changed = 0;

        Lead::withTrashed()
            ->whereNotNull('ip')
            ->where('created_at', '<', now()->subDays(Lead::IP_RETENTION_DAYS))
            ->chunkById(500, function ($leads) use (&$changed): void {
                foreach ($leads as $lead) {
                    $lead->forceFill(['ip_hash' => IpHash::make((string) $lead->ip), 'ip' => null])->saveQuietly();
                    $changed++;
                }
            });

        return $changed;
    }
}
