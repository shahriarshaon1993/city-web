<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugHistory;
use App\Models\Concerns\IsSiteSearchable;
use App\Models\Concerns\Publishable;
use App\Models\Contracts\PublishableEntity;
use App\Support\Media\UsesMedia;
use App\Support\News\PostBody;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Attributes\SearchUsingFullText;

/**
 * DM-POST: a news article (FR-NEWS-001). The body is purified Tiptap HTML whose library images carry
 * `data-asset-id`; they are recorded as usages with the cover and the share image (DM-MUSE).
 * Delete = Recycle bin (crud.md matrix).
 *
 * @property int $id
 * @property string|null $search_text
 * @property string $title
 * @property string $slug
 * @property string $excerpt
 * @property string|null $body
 * @property int|null $cover_asset_id
 * @property int $category_id
 * @property int|null $author_user_id
 * @property int|null $author_team_member_id
 * @property bool $is_featured
 * @property int $read_minutes
 * @property PublishStatus $publish_status
 * @property Carbon|null $published_at
 * @property int|null $pending_revision_id
 * @property int $lock_version
 * @property int|null $edited_by
 * @property Carbon|null $deleted_at
 */
#[Fillable(['title', 'slug', 'excerpt', 'body', 'cover_asset_id', 'category_id', 'author_user_id', 'is_featured', 'read_minutes', 'publish_status', 'published_at'])]
class Post extends Model implements PublishableEntity, Trashable, UsesMedia
{
    use BinBehaviour, HasRevisions, HasSeo, HasSlugHistory, IsSiteSearchable, Publishable, SoftDeletes;

    /** FR-NEWS-001 limits. */
    public const int TITLE_MAX = 110;

    public const int EXCERPT_MAX = 200;

    public const int TAGS_MAX = 10;

    public const int PROJECTS_MAX = 3;

    /** The cover is shown up to 1600 px wide (FR-NEWS-001). */
    public const int COVER_MIN_WIDTH = 1600;

    protected static function booted(): void
    {
        static::forceDeleted(MediaUsage::forget(...));
        static::forceDeleted(fn (self $post) => $post->tags()->detach());
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
        return ['is_featured' => 'boolean', 'read_minutes' => 'integer'];
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id')->withTrashed();
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_asset_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id')->withTrashed();
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->orderBy('tags.name');
    }

    /**
     * FR-NEWS-001: up to three related projects, in the order the editor chose.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'post_project')->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function path(): string
    {
        return '/news/'.$this->slug;
    }

    public function mediaUsageLabel(string $field): string
    {
        return match ($field) {
            'cover' => "{$this->title} › Cover image",
            'seo_og' => "{$this->title} › Social share image",
            default => "{$this->title} › Body",
        };
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.news.edit', $this);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void
    {
        if ($field === 'cover' && $this->cover_asset_id === $fromAssetId) {
            $this->forceFill(['cover_asset_id' => $toAssetId])->saveQuietly();
        }

        if ($field === 'body' && $this->body !== null) {
            $this->forceFill(['body' => PostBody::replaceAsset($this->body, $fromAssetId, $toAssetId)])->saveQuietly();
        }
    }

    public static function binType(): string
    {
        return 'post';
    }

    public static function binTypeLabel(): string
    {
        return 'Post';
    }

    public static function binPermission(): string
    {
        return 'news.manage';
    }

    public function binLabel(): string
    {
        return $this->title;
    }

    public function binUrl(): ?string
    {
        return route('admin.news.edit', $this);
    }
}
