<?php

namespace App\Models;

use App\Actions\Search\RefreshSearchText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * DM-SEO: the per-entity SEO overrides (spec/cms.md §8.5). A null field means "use the template
 * of §9.3"; the resolved values are built by `Support\Seo`.
 *
 * @property int $id
 * @property string $seoable_type
 * @property int $seoable_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $canonical_url
 * @property string $robots
 * @property string|null $og_title
 * @property string|null $og_description
 * @property int|null $og_image_asset_id
 * @property string $twitter_card
 * @property string|null $focus_keyword
 * @property bool $schema_enabled
 */
#[Fillable([
    'meta_title', 'meta_description', 'canonical_url', 'robots', 'og_title', 'og_description',
    'og_image_asset_id', 'twitter_card', 'focus_keyword', 'schema_enabled',
])]
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected static function booted(): void
    {
        // FR-SRCH-010: a page is searched by its meta description.
        static::saved(function (self $meta): void {
            $owner = $meta->seoable;

            if ($owner instanceof Page && $meta->wasChanged('meta_description')) {
                app(RefreshSearchText::class)->handle($owner);
            }
        });
    }

    /** @var list<string> */
    public const array ROBOTS = ['index,follow', 'noindex,follow', 'noindex,nofollow'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'robots' => 'index,follow',
        'twitter_card' => 'summary_large_image',
        'schema_enabled' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['schema_enabled' => 'boolean'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_asset_id');
    }
}
