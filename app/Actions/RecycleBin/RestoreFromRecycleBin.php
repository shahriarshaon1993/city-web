<?php

namespace App\Actions\RecycleBin;

use App\Actions\Activity\LogActivity;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Support\RecycleBin\RestoreConflictException;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * DEL-05: restore brings the item back as it was (publishable entities come back as Draft, that
 * rule lives in the entity's `binResolveConflicts`/restore hooks when 13.x adds them). A conflict
 * is raised as RestoreConflictException until the caller passes the user's answers.
 */
final class RestoreFromRecycleBin
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $resolution  answers to the item's conflicts, by field
     * @return Model&Trashable the restored item
     *
     * @throws RestoreConflictException when a decision is still missing
     * @throws ValidationException when the item no longer exists
     */
    public function handle(User $actor, RecycleBinEntry $entry, array $resolution = []): Model&Trashable
    {
        $item = $entry->item();

        if ($item === null) {
            $entry->delete();

            throw ValidationException::withMessages(['item' => __('This item no longer exists.')]);
        }

        $open = array_values(array_filter(
            $item->binRestoreConflicts(),
            fn ($conflict): bool => ! array_key_exists($conflict->field, $resolution),
        ));

        if ($open !== []) {
            throw new RestoreConflictException($open);
        }

        return DB::transaction(function () use ($actor, $entry, $item, $resolution): Model&Trashable {
            $item->binResolveConflicts($resolution);
            $item->restore();
            $entry->delete();

            $this->logActivity->handle(
                Str::plural($item::binType()),
                'restored',
                $item::binTypeLabel().' restored from Recycle bin',
                $actor,
                $item,
                ['label' => $item->binLabel()],
            );

            return $item;
        });
    }
}
