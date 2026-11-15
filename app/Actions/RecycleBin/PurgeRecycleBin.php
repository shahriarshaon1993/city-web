<?php

namespace App\Actions\RecycleBin;

use App\Models\RecycleBinEntry;

/**
 * DEL-06: the nightly auto-delete. Every entry whose `purge_at` has passed is removed for good;
 * entries without a date (users) stay. Scheduled in routes/console.php.
 */
final class PurgeRecycleBin
{
    public function __construct(private readonly DeleteForever $deleteForever) {}

    /**
     * @return int number of items removed
     */
    public function handle(): int
    {
        $purged = 0;

        RecycleBinEntry::query()
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', now())
            ->orderBy('id')
            ->each(function (RecycleBinEntry $entry) use (&$purged): void {
                $this->deleteForever->handle(null, $entry);
                $purged++;
            });

        return $purged;
    }
}
