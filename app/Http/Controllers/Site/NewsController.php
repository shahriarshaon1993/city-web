<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\PostResource;
use App\Models\Post;
use App\Models\PostCategory;
use App\Queries\Site\NewsQuery;
use App\Support\News\NewsCopy;
use App\Support\Pagination\PageLinks;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use App\Support\Seo\SeoOverrides;
use App\Support\Settings\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-12 `/news`, PG-13 `/news/category/{slug}` and `/news/tag/{slug}` (LAY-NEWS-LIST) and PG-14
 * `/news/{slug}` (LAY-NEWS-DETAIL), read from the posts table (FR-NEWS). A post dated in the future, a
 * draft or a deleted post is a 404 (AC-NEWS-01); a renamed one answers 301 (SEO-TEC-003); pages
 * beyond the last are a 404 (SEO-TEC-005). The preview of an unpublished version is `preview()`.
 */
class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $copy = NewsCopy::labels();
        $page = $this->pageNumber($request);
        $featured = NewsQuery::featured();

        return $this->archive($request, $page, [
            'kind' => 'index',
            'base' => '/news',
            'title' => $copy['index_title'],
            'intro' => $copy['index_intro'],
            'description' => $copy['index_description'],
            'active' => null,
            // PG-12: the featured post leads page 1 only; it is not repeated in the grid.
            'posts' => NewsQuery::query()->when($featured !== null, fn (Builder $query) => $query->whereKeyNot($featured?->id)),
            'featured' => $page === 1 ? $featured : null,
            'indexable' => true,
            'breadcrumbs' => Breadcrumbs::to($copy['index_title']),
        ]);
    }

    public function category(Request $request, string $category): Response|RedirectResponse
    {
        $row = NewsQuery::category($category);

        if ($row === null) {
            $moved = PostCategory::findBySlugHistory($category);

            abort_if($moved === null, 404);

            return redirect($moved->path(), 301);
        }

        $copy = NewsCopy::labels();

        return $this->archive($request, $this->pageNumber($request), [
            'kind' => 'category',
            'base' => $row->path(),
            'title' => $row->name,
            'intro' => (string) $row->description,
            'description' => (string) ($row->description ?: $copy['index_description']),
            'active' => $row->slug,
            'posts' => NewsQuery::query()->where('category_id', $row->id),
            'featured' => null,
            'indexable' => (int) $row->getAttribute('posts_count') > 0,
            'breadcrumbs' => Breadcrumbs::to($row->name, [[$copy['index_title'], '/news']]),
        ]);
    }

    public function tag(Request $request, string $tag): Response
    {
        $row = NewsQuery::tag($tag) ?? abort(404);
        $copy = NewsCopy::labels();
        $fill = fn (string $text): string => str_replace('{tag}', $row->name, $text);

        return $this->archive($request, $this->pageNumber($request), [
            'kind' => 'tag',
            'base' => $row->path(),
            'title' => $fill($copy['tag_title']),
            'intro' => $fill($copy['tag_intro']),
            'description' => $fill($copy['tag_description']),
            'active' => null,
            'posts' => NewsQuery::query()->whereHas('tags', fn (Builder $tags) => $tags->whereKey($row->id)),
            'featured' => null,
            // PG-13: a thin tag page stays out of the index but its links are followed.
            'indexable' => (int) $row->getAttribute('posts_count') >= NewsQuery::TAG_INDEX_MIN,
            'breadcrumbs' => Breadcrumbs::to($fill($copy['tag_title']), [[$copy['index_title'], '/news']]),
        ]);
    }

    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $post = NewsQuery::find($slug);

        if ($post === null) {
            $moved = Post::findBySlugHistory($slug);

            abort_unless($moved !== null && $moved->isPublished(), 404);

            return redirect($moved->path(), 301);
        }

        return $this->article($request, $post);
    }

    /**
     * CMS-PUB-005 / AC-NEWS-01: the same template from a hydrated copy (pending revision or saved version).
     * The route is signed, so no published check applies; the response is `noindex`.
     */
    public function preview(Request $request, Post $copy): Response
    {
        return $this->article($request, $copy, true);
    }

    private function article(Request $request, Post $post, bool $preview = false): Response
    {
        $copy = NewsCopy::labels();
        $article = PostResource::make($post)->resolve($request);
        $meta = $post->seoMeta;
        $cover = $article['cover'];
        $settings = Settings::group('seo');

        // The cover is the page's LCP image (article-cover preset, HERO-012).
        $seo = SeoOverrides::apply(
            [
                'meta_title' => $meta?->meta_title,
                'meta_description' => $meta?->meta_description,
                'robots' => $meta?->robots,
                'canonical_url' => $meta?->canonical_url,
                'og_image' => $meta?->ogImage?->ogUrl(),
            ],
            str_replace('{title}', $post->title, (string) $settings['title_template']),
            $post->excerpt,
            $post->cover?->ogUrl(),
            $cover['alt'] ?? null,
        )
            ->lcp($cover, null, '(min-width:1024px) 960px, 100vw')
            ->article();

        if ($preview) {
            $seo->noindex(false);
        }

        return Inertia::render('Site/NewsArticle', [
            'article' => $article,
            'related' => NewsQuery::related($post),
            'share' => NewsCopy::share($post->slug, $post->title),
            'breadcrumbs' => Breadcrumbs::to($post->title, [
                [$copy['index_title'], '/news'],
                [$article['category']['name'], $article['category']['url']],
            ]),
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    /**
     * @param  array{kind: string, base: string, title: string, intro: string, description: string, active: string|null, posts: Builder<Post>, featured: Post|null, indexable: bool, breadcrumbs: list<array{label: string, url: string|null}>}  $archive
     */
    private function archive(Request $request, int $page, array $archive): Response
    {
        $copy = NewsCopy::labels();
        $total = (clone $archive['posts'])->toBase()->getCountForPagination();
        $lastPage = max(1, (int) ceil($total / NewsQuery::PER_PAGE));

        abort_if($page > $lastPage, 404);

        $posts = NewsQuery::resolve($archive['posts']->forPage($page, NewsQuery::PER_PAGE)->get());
        $featured = $archive['featured'] === null ? null : NewsQuery::resolve([$archive['featured']])[0];
        $lead = $featured['cover'] ?? $posts[0]['cover'] ?? null;

        $title = $archive['kind'] === 'index' ? $copy['index_title'] : $archive['title'];

        $seo = SeoData::page($page > 1 ? "{$title} – Page {$page}" : $title, $archive['description'])
            ->lcp($lead, null, '(min-width:1280px) 720px, (min-width:1024px) 58vw, 92vw');

        if (! $archive['indexable']) {
            $seo->noindex();
        }

        return Inertia::render('Site/News', [
            'news' => [
                'kind' => $archive['kind'],
                'title' => $archive['title'],
                'intro' => $archive['intro'],
                'breadcrumbs' => $archive['breadcrumbs'],
                'active_category' => $archive['active'],
                'categories' => [
                    ['slug' => null, 'name' => $copy['all_label'], 'url' => '/news', 'count' => NewsQuery::total()],
                    ...NewsQuery::categories()->map(fn (PostCategory $category): array => [
                        'slug' => $category->slug,
                        'name' => $category->name,
                        'url' => $category->path(),
                        'count' => (int) $category->getAttribute('posts_count'),
                    ])->all(),
                ],
                'featured' => $featured,
                'posts' => $posts,
                'meta' => [
                    'page' => $page,
                    'last_page' => $lastPage,
                    'total' => $total,
                    'pagination' => PageLinks::build($page, $lastPage, fn (int $n): string => PageLinks::pageUrl($archive['base'], $n)),
                ],
            ],
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    /**
     * `?page=n` (n ≥ 1); NormalizeUrl has already removed `?page=1`. Anything
     * else that is not a positive integer is a 404.
     */
    private function pageNumber(Request $request): int
    {
        $page = $request->query('page');

        if ($page === null) {
            return 1;
        }

        abort_unless(is_string($page) && ctype_digit($page) && (int) $page >= 1, 404);

        return (int) $page;
    }
}
