<?php

namespace App\Actions\RecycleBin;

use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bulk "Delete forever" from the bin (the caller already asked for the typed confirmation).
 */
final class DeleteManyForever
{
    public function __construct(private readonly DeleteForever $deleteForever) {}

    /**
     * @param  Collection<int, RecycleBinEntry>  $entries
     */
    public function handle(User $actor, Collection $entries): int
    {
        foreach ($entries as $entry) {
            $this->deleteForever->handle($actor, $entry);
        }

        return $entries->count();
    }
}
