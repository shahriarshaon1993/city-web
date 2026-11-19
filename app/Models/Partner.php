<?php

namespace App\Models;

use App\Enums\PartnerCategory;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-PART / FR-SOC-002: a partner logo for the marquee block. Delete = Recycle bin.
 *
 * @property int $id
 * @property string $name
 * @property int|null $logo_asset_id
 * @property string|null $url
 * @property PartnerCategory $category
 * @property int $sort_order
 * @property bool $is_published
 */
#[Fillable(['name', 'logo_asset_id', 'url', 'category', 'sort_order', 'is_published'])]
class Partner extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, SoftDeletes;

    public const int NAME_MAX = 100;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['category' => PartnerCategory::class, 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_asset_id');
    }

    /**
     * @param  Builder<Partner>  $query
     * @return Builder<Partner>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function mediaUsageLabel(string $field): string
    {
        return "Partner · {$this->name} › Logo";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.partners.index');
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($this->logo_asset_id === $fromAssetId) {
            $this->forceFill(['logo_asset_id' => $toAssetId])->saveQuietly();
        }
    }

    public static function binType(): string
    {
        return 'partner';
    }

    public static function binTypeLabel(): string
    {
        return 'Partner';
    }

    public static function binPermission(): string
    {
        return 'social.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.partners.index');
    }
}
