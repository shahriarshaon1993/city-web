<?php

namespace App\Models;

use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugHistory;
use App\Models\Concerns\Publishable;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\RestoreConflict;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-LOC: a city or an area (FR-LOC-001). Projects link to an area. Cities and areas share one slug
 * namespace. Delete = Recycle bin; a city with areas and an area with projects cannot be deleted
 * (crud.md matrix). The hero image is a DM-MUSE usage with the field `hero`.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property LocationType $type
 * @property string $name
 * @property string $slug
 * @property string|null $intro
 * @property list<array{label: string, value: string}>|null $facts
 * @property array<string, string>|null $segment_intros
 * @property int|null $hero_asset_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $zoom
 * @property PublishStatus $publish_status
 * @property Carbon|null $published_at
 * @property int $sort_order
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'parent_id', 'type', 'name', 'slug', 'intro', 'facts', 'segment_intros', 'hero_asset_id',
    'latitude', 'longitude', 'zoom', 'publish_status', 'published_at', 'sort_order',
])]
class Location extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, HasSeo, HasSlugHistory, Publishable, SoftDeletes;

    /** FR-LOC-002 limits. */
    public const int FACTS_MAX = 8;

    public const int NEARBY_MAX = 6;

    public const int ZOOM_MIN = 11;

    public const int ZOOM_MAX = 16;

    protected static function booted(): void
    {
        static::forceDeleted(MediaUsage::forget(...));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'facts' => 'array',
            'segment_intros' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'zoom' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCities(Builder $query): Builder
    {
        return $query->where('type', LocationType::City->value);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAreas(Builder $query): Builder
    {
        return $query->where('type', LocationType::Area->value);
    }

    /**
     * The city of an area (trashed ones included, so the Recycle bin can tell).
     *
     * @return BelongsTo<Location, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    /**
     * The areas of a city.
     *
     * @return HasMany<Location, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * FR-LOC-002: up to 6 nearby areas picked by hand.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function nearby(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'location_nearby', 'location_id', 'nearby_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function hero(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_asset_id');
    }

    public function isCity(): bool
    {
        return $this->type === LocationType::City;
    }

    public function mediaUsageLabel(string $field): string
    {
        return match ($field) {
            'seo_og' => "{$this->name} › Social share image",
            default => "{$this->name} › Hero image",
        };
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return null;
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($field === 'hero' && $this->hero_asset_id === $fromAssetId) {
            $this->forceFill(['hero_asset_id' => $toAssetId])->saveQuietly();
        }
    }

    public static function binType(): string
    {
        return 'location';
    }

    public static function binTypeLabel(): string
    {
        return 'Location';
    }

    public static function binPermission(): string
    {
        return 'catalog.manage';
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    /** DEL-03: a city with areas, or an area with projects, stays until those are moved or deleted. */
    public function binUsageBlock(User $actor): ?string
    {
        if ($this->isCity()) {
            $areas = $this->children()->count();

            return $areas === 0 ? null : "“{$this->name}” has {$areas} ".($areas === 1 ? 'area' : 'areas').'. Delete or move them first.';
        }

        $projects = $this->projects()->count();

        return $projects === 0 ? null : "“{$this->name}” has {$projects} ".($projects === 1 ? 'project' : 'projects').'. Move them to another area or delete them first.';
    }

    /**
     * DEL-05: an area cannot come back while its city is in the bin.
     *
     * @return list<RestoreConflict>
     */
    public function binRestoreConflicts(): array
    {
        $city = $this->parent;

        if ($city === null || ! $city->trashed()) {
            return [];
        }

        return [new RestoreConflict('parent', 'parent', "The city “{$city->name}” is in the Recycle bin.")];
    }

    /**
     * @param  array<string, mixed>  $resolution
     */
    public function binResolveConflicts(array $resolution): void
    {
        if (($resolution['parent'] ?? false) === true) {
            $this->parent?->restore();
        }
    }
}
