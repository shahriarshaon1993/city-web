<?php

namespace App\Actions\Media;

use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * DM-MUSE: an entity field saves its chosen assets here (in order), so the library knows where an
 * asset is used (CMS-MED-007) and refuses to delete it (CMS-MED-008). Called by the entity's own
 * save Action inside its transaction.
 */
final class SyncMediaUsages
{
    /**
     * @param  list<int>  $assetIds  in display order; empty clears the field
     */
    public function handle(Model $usable, string $field, array $assetIds): void
    {
        MediaUsage::query()
            ->where('usable_type', $usable->getMorphClass())
            ->where('usable_id', $usable->getKey())
            ->where('field', $field)
            ->delete();

        foreach (array_values(array_unique($assetIds)) as $position => $assetId) {
            MediaUsage::query()->create([
                'media_asset_id' => $assetId,
                'usable_type' => $usable->getMorphClass(),
                'usable_id' => $usable->getKey(),
                'field' => $field,
                'position' => $position,
            ]);
        }
    }
}
