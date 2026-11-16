<?php

namespace App\Models;

use App\Actions\Projects\RefreshProjectDerivedFields;
use App\Enums\BookingState;
use App\Enums\Lifecycle;
use App\Enums\PublishStatus;
use App\Enums\Segment;
use App\Enums\VideoType;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugHistory;
use App\Models\Concerns\Publishable;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * DM-PROJ: the listing entity (FR-PROJ). The size, bed and bath ranges and `price_from_bdt` are
 * denormalised from the unit types by `RefreshProjectDerivedFields`; never edit them by hand
 * (`price_from_bdt` is the one editable figure, once `price_from_manual` is on).
 *
 * Hidden prices never leave the server (AC-PROJ-01): read prices through `publicPriceFrom()` and
 * `ProjectUnitType::publicPrice()`, never straight from the columns, when shaping a public prop.
 * Media fields are DM-MUSE usages: `cover`, `gallery`, `highlights`, `video`, `video_poster`,
 * `video_captions`, `documents`, `seo_og`; `SyncProjectMediaUsages` keeps them true.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property Segment $segment
 * @property Lifecycle $lifecycle
 * @property BookingState $booking_state
 * @property int $location_id
 * @property string|null $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float|null $land_area_katha
 * @property string|null $storeys
 * @property int|null $total_units
 * @property int|null $car_parks
 * @property int|null $units_per_floor
 * @property int|null $lifts
 * @property int|null $basements
 * @property int|null $road_width_ft
 * @property string|null $facing
 * @property string|null $orientation
 * @property Carbon|null $launch_date
 * @property Carbon|null $handover_date
 * @property string|null $rajuk_approval_no
 * @property string|null $architect
 * @property string|null $structural_engineer
 * @property string|null $contractor
 * @property list<array{label: string, value: string}>|null $custom_facts
 * @property string|null $overview
 * @property string|null $design_narrative
 * @property list<array{asset_id: int, title: string, text: string}>|null $highlights
 * @property list<array{asset_id: int, category: string, caption: string|null}>|null $gallery
 * @property int|null $cover_asset_id
 * @property VideoType $video_type
 * @property string|null $video_url
 * @property int|null $video_asset_id
 * @property int|null $video_poster_asset_id
 * @property int|null $video_captions_asset_id
 * @property string|null $virtual_tour_url
 * @property bool $show_price
 * @property int|null $price_from_bdt
 * @property bool $price_from_manual
 * @property string|null $price_note
 * @property bool $is_featured
 * @property int|null $featured_order
 * @property bool $brochure_gated
 * @property int|null $default_assignee_id
 * @property string|null $whatsapp_override
 * @property int|null $size_min_sft
 * @property int|null $size_max_sft
 * @property int|null $beds_min
 * @property int|null $beds_max
 * @property int|null $baths_min
 * @property int|null $baths_max
 * @property PublishStatus $publish_status
 * @property Carbon|null $published_at
 * @property int|null $pending_revision_id
 * @property int $lock_version
 * @property string|null $archived_redirect_to
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name', 'slug', 'tagline', 'segment', 'lifecycle', 'booking_state', 'location_id', 'address',
    'latitude', 'longitude', 'land_area_katha', 'storeys', 'total_units', 'car_parks', 'units_per_floor',
    'lifts', 'basements', 'road_width_ft', 'facing', 'orientation', 'launch_date', 'handover_date',
    'rajuk_approval_no', 'architect', 'structural_engineer', 'contractor', 'custom_facts', 'overview',
    'design_narrative', 'highlights', 'gallery', 'cover_asset_id', 'video_type', 'video_url',
    'video_asset_id', 'video_poster_asset_id', 'video_captions_asset_id', 'virtual_tour_url', 'show_price',
    'price_from_bdt', 'price_from_manual', 'price_note', 'is_featured', 'featured_order', 'brochure_gated',
    'default_assignee_id', 'whatsapp_override', 'publish_status', 'published_at', 'archived_redirect_to',
])]
class Project extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, HasSeo, HasSlugHistory, Publishable, SoftDeletes;

    /** FR-PROJ limits. */
    public const int GALLERY_MAX = 60;

    public const int GALLERY_MIN_TO_PUBLISH = 4;

    public const int HIGHLIGHTS_MAX = 8;

    public const int CUSTOM_FACTS_MAX = 12;

    public const int SIMILAR_MAX = 3;

    public const int NAME_MAX = 90;

    public const int SLUG_MAX = 80;

    protected static function booted(): void
    {
        // Gone for good: the rows below cascade in the database without model events, so their usages go now.
        static::forceDeleting(function (self $project): void {
            $project->unitTypes()->pluck('id')->each(fn (int $id) => MediaUsage::query()
                ->where('usable_type', (new ProjectUnitType)->getMorphClass())->where('usable_id', $id)->delete());
            $project->progressUpdates()->pluck('id')->each(fn (int $id) => MediaUsage::query()
                ->where('usable_type', (new ProjectProgressUpdate)->getMorphClass())->where('usable_id', $id)->delete());
            MediaUsage::forget($project);
        });

        // Turning "set manually" off hands the figure back to the unit types.
        static::saved(function (self $project): void {
            if ($project->wasChanged('price_from_manual') && ! $project->price_from_manual) {
                app(RefreshProjectDerivedFields::class)->handle($project);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'segment' => Segment::class,
            'lifecycle' => Lifecycle::class,
            'booking_state' => BookingState::class,
            'video_type' => VideoType::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'land_area_katha' => 'float',
            'launch_date' => 'date',
            'handover_date' => 'date',
            'custom_facts' => 'array',
            'highlights' => 'array',
            'gallery' => 'array',
            'show_price' => 'boolean',
            'price_from_bdt' => 'integer',
            'price_from_manual' => 'boolean',
            'is_featured' => 'boolean',
            'featured_order' => 'integer',
            'brochure_gated' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    /**
     * @return HasMany<ProjectUnitType, $this>
     */
    public function unitTypes(): HasMany
    {
        return $this->hasMany(ProjectUnitType::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_project')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Newest first (FR-PROJ-005).
     *
     * @return HasMany<ProjectProgressUpdate, $this>
     */
    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProjectProgressUpdate::class)->orderByDesc('reported_on')->orderByDesc('id');
    }

    /**
     * @return HasMany<ProjectNearbyPlace, $this>
     */
    public function nearbyPlaces(): HasMany
    {
        return $this->hasMany(ProjectNearbyPlace::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ProjectDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * FR-PROJ-007: up to 3 hand-picked similar projects (empty = automatic).
     *
     * @return BelongsToMany<Project, $this>
     */
    public function similar(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'project_similar', 'project_id', 'similar_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function videoAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'video_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function videoPoster(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'video_poster_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function videoCaptions(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'video_captions_asset_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function pendingRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'pending_revision_id');
    }

    /**
     * @return MorphMany<Revision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable');
    }

    /** The "From ৳ …" figure a visitor may see, or null when the project hides its prices (AC-PROJ-01). */
    public function publicPriceFrom(): ?int
    {
        return $this->show_price ? $this->price_from_bdt : null;
    }

    public function mediaUsageLabel(string $field): string
    {
        $label = match ($field) {
            'cover' => 'Cover image',
            'gallery' => 'Gallery',
            'highlights' => 'Feature highlights',
            'video' => 'Video file',
            'video_poster' => 'Video poster',
            'video_captions' => 'Video captions',
            'documents' => 'Documents',
            'seo_og' => 'Social share image',
            default => $field,
        };

        return "{$this->name} › {$label}";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return null;
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        $swap = static fn (?int $id): ?int => $id === $fromAssetId ? $toAssetId : $id;

        match ($field) {
            'cover' => $this->cover_asset_id = $swap($this->cover_asset_id),
            'video' => $this->video_asset_id = $swap($this->video_asset_id),
            'video_poster' => $this->video_poster_asset_id = $swap($this->video_poster_asset_id),
            'video_captions' => $this->video_captions_asset_id = $swap($this->video_captions_asset_id),
            'gallery' => $this->swapInGallery($fromAssetId, $toAssetId),
            'highlights' => $this->swapInHighlights($fromAssetId, $toAssetId),
            'documents' => $this->documents()->where('media_asset_id', $fromAssetId)->update(['media_asset_id' => $toAssetId]),
            default => null,
        };

        if ($this->isDirty()) {
            $this->saveQuietly();
        }
    }

    private function swapInGallery(int $fromAssetId, int $toAssetId): void
    {
        $items = $this->gallery ?? [];

        foreach ($items as $position => $item) {
            if ($item['asset_id'] === $fromAssetId) {
                $items[$position]['asset_id'] = $toAssetId;
            }
        }

        $this->gallery = $items;
    }

    private function swapInHighlights(int $fromAssetId, int $toAssetId): void
    {
        $items = $this->highlights ?? [];

        foreach ($items as $position => $item) {
            if ($item['asset_id'] === $fromAssetId) {
                $items[$position]['asset_id'] = $toAssetId;
            }
        }

        $this->highlights = $items;
    }

    public static function binType(): string
    {
        return 'project';
    }

    public static function binTypeLabel(): string
    {
        return 'Project';
    }

    /** Content Editors cannot delete projects (spec/projects.md permissions). */
    public static function binPermission(): string
    {
        return 'projects.delete';
    }

    public function binLabel(): string
    {
        return $this->name;
    }
}
