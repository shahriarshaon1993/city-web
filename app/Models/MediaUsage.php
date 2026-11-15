<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DM-MUSE: "this entity field uses this asset" (CMS-MED-007). Entities write these rows when a
 * media field is saved; the library reads them for the usage list and the delete guard.
 *
 * @property int $id
 * @property int $media_asset_id
 * @property string $usable_type
 * @property int $usable_id
 * @property string $field
 * @property int $position
 */
#[Fillable(['media_asset_id', 'usable_type', 'usable_id', 'field', 'position'])]
class MediaUsage extends Model
{
    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
