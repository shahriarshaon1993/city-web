<?php

namespace App\Actions\RecycleBin;

use App\Actions\Activity\LogActivity;
use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DEL-07: removes a bin item for good, with its private files. The activity entry carries no
 * personal data: the type and id only, never the item's name. `$actor` is null for the nightly purge.
 */
final class DeleteForever
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(?User $actor, RecycleBinEntry $entry): void
    {
        DB::transaction(function () use ($actor, $entry): void {
            $item = $entry->item();

            $this->logActivity->handle(
                Str::plural($entry->type),
                $actor === null ? 'purged' : 'deleted_forever',
                ($item === null ? ucfirst($entry->type) : $item::binTypeLabel()).($actor === null ? ' removed by the nightly clean-up' : ' deleted forever'),
                $actor,
                null,
                ['type' => $entry->type, 'id' => $entry->trashable_id],
            );

            if ($item !== null) {
                $item->binForceDeleting();
                $item->forceDelete();
            }

            $entry->delete();
        });
    }
}
