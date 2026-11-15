<?php

namespace App\Models;

use App\Support\Media\MediaKind;
use App\Support\Media\Renditions;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * DM-MASSET: one library item owning one spatie file (CMS-MED-001). Entities reference it through
 * `media_usages` (DM-MUSE), so one upload is reused anywhere. Deleting an unused item moves it to
 * the Recycle bin for 30 days (CMS-MED-008, DEL-06).
 *
 * @property int $id
 * @property int|null $folder_id
 * @property MediaKind $kind
 * @property string $title
 * @property string|null $alt
 * @property string|null $caption
 * @property string|null $credit
 * @property bool $is_decorative
 * @property int $focal_x
 * @property int $focal_y
 * @property string|null $dominant_color
 * @property list<string>|null $tags
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration_s
 * @property string $conversions_status pending|ready|failed
 * @property int|null $uploaded_by
 */
#[Fillable(['folder_id', 'kind', 'title', 'alt', 'caption', 'credit', 'is_decorative', 'focal_x', 'focal_y', 'dominant_color', 'tags', 'width', 'height', 'duration_s', 'conversions_status', 'uploaded_by'])]
class MediaAsset extends Model implements HasMedia, Trashable
{
    use BinBehaviour, InteractsWithMedia, SoftDeletes;

    /** The one collection holding the library file. */
    public const string COLLECTION = 'file';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_READY = 'ready';

    public const string STATUS_FAILED = 'failed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'is_decorative' => 'boolean',
            'tags' => 'array',
            'focal_x' => 'integer',
            'focal_y' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        // One file per item: "Replace file" adds the new one and removes the old (and its folder).
        $this->addMediaCollection(self::COLLECTION)->singleFile();
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<MediaUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::COLLECTION);
    }

    /** Public URL of the original file. */
    public function url(): ?string
    {
        return $this->file()?->getUrl();
    }

    /**
     * What `GenerateConversions` wrote (srcset sources, the JPEG fallback, the `og` crop); null until it has run.
     */
    public function renditions(): ?Renditions
    {
        return Renditions::fromStored($this->file()?->getCustomProperty('renditions'));
    }

    /** URL of the 400 px `thumb` conversion; the original for SVG and tiny images; null for videos and PDFs. */
    public function thumbUrl(): ?string
    {
        $media = $this->file();

        if ($media === null) {
            return null;
        }

        return match ($this->kind) {
            MediaKind::Svg => $media->getUrl(),
            MediaKind::Image => ($thumb = $this->renditions()?->webp[0] ?? null) === null
                ? $media->getUrl()
                : $this->diskUrl($media, $thumb['path']),
            default => null,
        };
    }

    /** URL of the 1200×630 focal crop for `seo.og.image`, once the conversions are ready. */
    public function ogUrl(): ?string
    {
        $media = $this->file();
        $og = $this->renditions()?->og;

        return $media === null || $og === null ? null : $this->diskUrl($media, $og);
    }

    public function diskUrl(Media $media, string $path): string
    {
        return Storage::disk($media->disk)->url($path);
    }

    public static function binType(): string
    {
        return 'media_asset';
    }

    public static function binTypeLabel(): string
    {
        return 'Media file';
    }

    /** Content Editors may delete unused files (spec/admin.md §10.2), so they see and restore them too. */
    public static function binPermission(): string
    {
        return 'media.delete-unused';
    }

    /** CMS-MED-008, DEL-06: purged after 30 days. */
    public static function binRetentionDays(): ?int
    {
        return 30;
    }

    public function binLabel(): string
    {
        return $this->title;
    }

    public function binThumbnail(): ?string
    {
        return $this->thumbUrl();
    }

    public function binUrl(): ?string
    {
        return route('admin.media.index', ['search' => $this->title]);
    }

    /** CMS-MED-008 / DEL-03: a used file cannot be deleted. */
    public function binUsageBlock(User $actor): ?string
    {
        $uses = $this->usages()->count();

        if ($uses === 0) {
            return null;
        }

        $places = $uses === 1 ? '1 place' : "{$uses} places";

        return "“{$this->title}” is used in {$places}. Use “Replace everywhere” to swap it for another file first, or remove it from the pages that use it.";
    }
}
