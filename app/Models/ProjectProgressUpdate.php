<?php

namespace App\Models;

use App\Enums\ProgressStage;
use App\Support\Media\UsesMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * DM-PROG: one dated construction update (FR-PROJ-005). Its photos (≤ 12) are DM-MUSE usages with the
 * field `photos`, in `position` order; they have no column of their own.
 *
 * @property int $id
 * @property int $project_id
 * @property Carbon $reported_on
 * @property int $percent
 * @property ProgressStage $stage
 * @property string|null $note
 */
#[Fillable(['project_id', 'reported_on', 'percent', 'stage', 'note'])]
class ProjectProgressUpdate extends Model implements UsesMedia
{
    public const int PHOTOS_MAX = 12;

    protected static function booted(): void
    {
        static::deleted(MediaUsage::forget(...));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['reported_on' => 'date', 'percent' => 'integer', 'stage' => ProgressStage::class];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return MorphToMany<MediaAsset, $this>
     */
    public function photos(): MorphToMany
    {
        return $this->morphToMany(MediaAsset::class, 'usable', 'media_usages', 'usable_id', 'media_asset_id')
            ->wherePivot('field', 'photos')
            ->orderByPivot('position');
    }

    public function mediaUsageLabel(string $field): string
    {
        $project = $this->project()->withTrashed()->first()->name ?? 'Project';

        return "{$project} › Progress {$this->reported_on->format('M Y')} › Photos";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return null;
    }

    /** The photos live only in their usage rows, which "Replace everywhere" has already moved. */
    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void {}
}
