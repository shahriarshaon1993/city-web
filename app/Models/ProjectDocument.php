<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DM-PDOC: a PDF of a project (brochure, price list, specification…). A gated document is only
 * downloaded through a signed URL after a lead (AC-PROJ-05). Its asset is a DM-MUSE usage of the
 * project with the field `documents`.
 *
 * @property int $id
 * @property int $project_id
 * @property int $media_asset_id
 * @property string $title
 * @property DocumentKind $kind
 * @property bool $is_gated
 * @property int $sort_order
 */
#[Fillable(['project_id', 'media_asset_id', 'title', 'kind', 'is_gated', 'sort_order'])]
class ProjectDocument extends Model
{
    /** FR-PROJ: additional documents, at most 5 (the brochure is one of them). */
    public const int MAX_PER_PROJECT = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => DocumentKind::class, 'is_gated' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
