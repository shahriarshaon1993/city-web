<?php

namespace App\Actions\Monitoring;

use App\Models\NotFoundLog;

/**
 * SEO-TEC-009: entries not seen for 90 days are purged (nightly, `not-found:purge`).
 */
final class PurgeNotFoundLogs
{
    public function handle(): int
    {
        return NotFoundLog::query()->where('last_seen_at', '<', now()->subDays(NotFoundLog::RETENTION_DAYS))->delete();
    }
}
