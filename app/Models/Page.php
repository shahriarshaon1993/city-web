<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugHistory;
use App\Models\Concerns\IsSiteSearchable;
use App\Models\Concerns\Publishable;
use App\Models\Contracts\PublishableEntity;
use App\Support\Blocks\MediaPaths;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Attributes\SearchUsingFullText;

/**
 * DM-PAGE: a builder page (blocks, CMS-PB-001) or a legal page (one rich-text block and a "last
 * updated" date). System pages are created by the seeder: they cannot be deleted or re-slugged
 * (crud.md matrix). Media inside the blocks is recorded against the page (DM-MUSE).
 *
 * @property int $id
 * @property string|null $search_text
 * @property string $title
 * @property PublishStatus $publish_status
 * @property Carbon|null $published_at
 * @property string $slug
 * @property string $template
 * @property bool $is_system
 * @property Carbon|null $last_reviewed_at
 * @property Carbon|null $deleted_at
 * @property int|null $pending_revision_id
 * @property int $lock_version
 * @property int|null $edited_by
 */
#[Fillable(['title', 'slug', 'template', 'is_system', 'publish_status', 'published_at', 'last_reviewed_at'])]
class Page extends Model implements PublishableEntity, Trashable, UsesMedia
{
    use BinBehaviour, HasRevisions, HasSeo, HasSlugHistory, IsSiteSearchable, Publishable, SoftDeletes;

    public const string BUILDER = 'builder';

    public const string LEGAL = 'legal';

    /** CMS-PB-005. */
    public const int BLOCKS_MAX = 30;

    /** The slug of the home page (PG-01). */
    public const string HOME = 'home';

    /**
     * Public path of each system page whose address is not `/{slug}` (PG-01, PG-08).
     *
     * @var array<string, string>
     */
    public const array SYSTEM_PATHS = [self::HOME => '/', 'leadership' => '/about/leadership'];

    protected static function booted(): void
    {
        static::forceDeleted(MediaUsage::forget(...));
    }

    /**
     * FR-SRCH-010: matched by one boolean-mode FULLTEXT index over the title and `search_text`.
     *
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['title', 'search_text'], ['mode' => 'boolean'])]
    public function toSearchableArray(): array
    {
        return ['title' => $this->title, 'search_text' => $this->search_text];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'last_reviewed_at' => 'date'];
    }

    /**
     * @return HasMany<PageBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isLegal(): bool
    {
        return $this->template === self::LEGAL;
    }

    /** The public address, e.g. `/`, `/about`, `/about/leadership`, `/buyer-guide`. */
    public function path(): string
    {
        return self::SYSTEM_PATHS[$this->slug] ?? '/'.$this->slug;
    }

    /**
     * The parents of this page in the breadcrumb (page_header block), beyond Home.
     *
     * @return list<array{0: string, 1: string}> label, path
     */
    public function breadcrumbParents(): array
    {
        return $this->slug === 'leadership' ? [['About us', '/about']] : [];
    }

    public function mediaUsageLabel(string $field): string
    {
        return $field === 'seo_og' ? "{$this->title} › Social share image" : "{$this->title} › Page blocks";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return null;
    }

    /** Block data holds the asset ids, so a swap rewrites them in the rows (DEC-78). */
    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if (! str_starts_with($field, 'blocks.')) {
            return;
        }

        foreach ($this->blocks()->get() as $block) {
            $data = MediaPaths::replace($block, $fromAssetId, $toAssetId);

            if ($data !== null) {
                $block->forceFill(['data' => $data])->saveQuietly();
            }
        }
    }

    public static function binType(): string
    {
        return 'page';
    }

    public static function binTypeLabel(): string
    {
        return 'Page';
    }

    public static function binPermission(): string
    {
        return 'pages.manage';
    }

    public function binLabel(): string
    {
        return $this->title;
    }

    /** DEL-03: system pages are part of the site and stay. */
    public function binUsageBlock(User $actor): ?string
    {
        return $this->is_system ? "“{$this->title}” is a system page and cannot be deleted." : null;
    }
}
