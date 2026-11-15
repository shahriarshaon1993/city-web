<?php

namespace App\Actions\RecycleBin;

use App\Actions\Activity\LogActivity;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * DEL-01/02: Delete = soft delete + a bin row with the auto-delete date (DEL-06). The entity's
 * own guard (DEL-03) runs first and refuses with its sentence.
 */
final class MoveToRecycleBin
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException when the item is in use or a limit forbids it
     */
    public function handle(User $actor, Model&Trashable $item): RecycleBinEntry
    {
        $block = $item->binUsageBlock($actor);

        if ($block !== null) {
            throw ValidationException::withMessages(['item' => $block]);
        }

        return DB::transaction(function () use ($actor, $item): RecycleBinEntry {
            $item->delete();
            $item->binTrashed();

            $days = $item::binRetentionDays();

            $entry = RecycleBinEntry::query()->updateOrCreate(
                ['type' => $item::binType(), 'trashable_id' => $item->getKey()],
                [
                    'label' => Str::limit($item->binLabel(), 190, ''),
                    'thumbnail' => $item->binThumbnail(),
                    'deleted_by' => $actor->getKey(),
                    'deleted_at' => now(),
                    'purge_at' => $days === null ? null : now()->addDays($days),
                ],
            );

            $this->logActivity->handle(
                Str::plural($item::binType()),
                'deleted',
                $item::binTypeLabel().' moved to Recycle bin',
                $actor,
                $item,
                ['label' => $entry->label],
            );

            return $entry;
        });
    }
}
