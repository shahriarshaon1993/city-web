<?php

namespace App\Queries\Site;

use App\Http\Resources\Site\NewsCardResource;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The public reads of the news module (FR-NEWS, PG-12…14): published posts only — a post dated in the
 * future is not published yet (AC-NEWS-01) — newest first, with what a card needs loaded.
 */
final class NewsQuery
{
    /** PG-12: nine posts per page. */
    public const int PER_PAGE = 9;

    /** SEO-ONP / PG-13: a tag page is indexable from this many published posts. */
    public const int TAG_INDEX_MIN = 3;

    /** Relations a card needs: the category and the cover with its media row. */
    public const array WITH = ['category', 'cover.media'];

    /**
     * @return Builder<Post>
     */
    public static function query(): Builder
    {
        return Post::query()->published()->with(self::WITH)->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * The article of a published post, with everything PG-14 shows.
     */
    public static function find(string $slug): ?Post
    {
        return Post::query()->published()->where('slug', $slug)
            ->with([...self::WITH, 'author', 'tags', 'projects', 'seoMeta.ogImage.media'])->first();
    }

    /** PG-12: the newest published post with the featured flag. */
    public static function featured(): ?Post
    {
        return self::query()->where('is_featured', true)->first();
    }

    /**
     * FR-NEWS-002 order, each with the number of published posts.
     *
     * @return Collection<int, PostCategory>
     */
    public static function categories(): Collection
    {
        return PostCategory::query()
            ->withCount(['posts' => fn ($posts) => $posts->published()])
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public static function category(string $slug): ?PostCategory
    {
        return PostCategory::query()->where('slug', $slug)
            ->withCount(['posts' => fn ($posts) => $posts->published()])->first();
    }

    public static function tag(string $slug): ?Tag
    {
        return Tag::query()->where('slug', $slug)
            ->withCount(['posts' => fn ($posts) => $posts->published()])->first();
    }

    /** The number of published posts (the "All" pill). */
    public static function total(): int
    {
        return Post::query()->published()->count();
    }

    /**
     * Home block and listing cards, newest published first.
     *
     * @return list<array<string, mixed>>
     */
    public static function cards(?string $categorySlug = null, ?int $limit = null): array
    {
        $posts = self::query()
            ->when($categorySlug !== null, fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug)))
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->get();

        return self::resolve($posts);
    }

    /**
     * FR-NEWS-007: the three newest published posts of the same category, excluding itself; the newest
     * overall fill any gap.
     *
     * @return list<array<string, mixed>>
     */
    public static function related(Post $post, int $count = 3): array
    {
        $same = self::query()->whereKeyNot($post->id)->where('category_id', $post->category_id)->limit($count)->get();

        if ($same->count() < $count) {
            $fill = self::query()->whereKeyNot($post->id)->whereNotIn('id', $same->modelKeys())->limit($count - $same->count())->get();
            $same = $same->concat($fill);
        }

        return self::resolve($same);
    }

    /**
     * @param  iterable<Post>  $posts  with {@see self::WITH} loaded
     * @return list<array<string, mixed>>
     */
    public static function resolve(iterable $posts): array
    {
        $cards = [];

        foreach ($posts as $post) {
            $cards[] = NewsCardResource::make($post)->resolve();
        }

        return $cards;
    }
}
