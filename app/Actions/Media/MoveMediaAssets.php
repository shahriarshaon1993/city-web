<?php

namespace App\Actions\Media;

use App\Actions\Activity\LogActivity;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Bulk "Move to folder" of the library (CMS-MED-002); `null` = Unfiled.
 */
final class MoveMediaAssets
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  iterable<MediaAsset>  $assets
     * @return int how many changed folder
     */
    public function handle(User $actor, iterable $assets, ?MediaFolder $folder): int
    {
        return DB::transaction(function () use ($actor, $assets, $folder): int {
            $moved = 0;

            foreach ($assets as $asset) {
                if ($asset->folder_id === $folder?->id) {
                    continue;
                }

                $asset->update(['folder_id' => $folder?->id]);
                $moved++;
            }

            if ($moved > 0) {
                $this->logActivity->handle('media', 'moved', 'Media moved to a folder', $actor, $folder, [
                    'count' => $moved,
                    'folder' => $folder->name ?? 'Unfiled',
                ]);
            }

            return $moved;
        });
    }
}
