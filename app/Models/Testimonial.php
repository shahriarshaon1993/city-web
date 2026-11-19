<?php

namespace App\Models;

use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-TEST / FR-SOC-001: a customer quote (plain text, no rating). Delete = Recycle bin; nothing
 * depends on a testimonial, so there is no usage guard.
 *
 * @property int $id
 * @property string $quote
 * @property string $person_name
 * @property string|null $designation
 * @property int|null $project_id
 * @property int|null $photo_asset_id
 * @property bool $is_nrb
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['quote', 'person_name', 'designation', 'project_id', 'photo_asset_id', 'is_nrb', 'sort_order', 'is_published'])]
class Testimonial extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, SoftDeletes;

    public const int QUOTE_MIN = 40;

    public const int QUOTE_MAX = 600;

    public const int NAME_MAX = 100;

    public const int DESIGNATION_MAX = 120;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_nrb' => 'boolean', 'is_published' => 'boolean', 'sort_order' => 'integer'];
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
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_asset_id');
    }

    /**
     * @param  Builder<Testimonial>  $query
     * @return Builder<Testimonial>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function mediaUsageLabel(string $field): string
    {
        return "Testimonial · {$this->person_name} › Photo";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.testimonials.edit', $this);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($this->photo_asset_id === $fromAssetId) {
            $this->forceFill(['photo_asset_id' => $toAssetId])->saveQuietly();
        }
    }

    public static function binType(): string
    {
        return 'testimonial';
    }

    public static function binTypeLabel(): string
    {
        return 'Testimonial';
    }

    public static function binPermission(): string
    {
        return 'social.manage';
    }

    public function binLabel(): string
    {
        return $this->person_name;
    }

    public function binUrl(): ?string
    {
        return route('admin.testimonials.edit', $this);
    }
}
