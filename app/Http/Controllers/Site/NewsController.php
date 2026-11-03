<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoNews;
use App\Support\Pagination\PageLinks;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-12 `/news`, PG-13 `/news/category/{slug}` and `/news/tag/{slug}`
 * (LAY-NEWS-LIST) and PG-14 `/news/{slug}` (LAY-NEWS-DETAIL). A post dated in
 * the future is a 404 (AC-NEWS-01); pages beyond the last are a 404
 * (SEO-TEC-005). 16.3 swaps DemoNews for the Post model and its Resources.
 *
 * @phpstan-import-type Post from DemoNews
 */
class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $copy = DemoNews::copy();
        $page = $this->pageNumber($request);
        $featured = DemoNews::featured();

        return $this->archive($request, $page, [
            'kind' => 'index',
            'base' => '/news',
            'title' => $copy['index_title'],
            'intro' => $copy['index_intro'],
            'description' => $copy['index_description'],
            'active' => null,
            'posts' => DemoNews::published($featured['slug'] ?? null),
            // PG-12: the featured post leads page 1 only; it is not repeated in the grid.
            'featured' => $page === 1 ? $featured : null,
            'indexable' => true,
            'breadcrumbs' => Breadcrumbs::to($copy['index_title']),
        ]);
    }

    public function category(Request $request, string $category): Response
    {
        $row = DemoNews::category($category) ?? abort(404);
        $copy = DemoNews::copy();

        return $this->archive($request, $this->pageNumber($request), [
            'kind' => 'category',
            'base' => $row['url'],
            'title' => $row['name'],
            'intro' => $row['description'],
            'description' => $row['description'],
            'active' => $category,
            'posts' => DemoNews::inCategory(DemoNews::published(), $category),
            'featured' => null,
            'indexable' => $row['count'] > 0,
            'breadcrumbs' => Breadcrumbs::to($row['name'], [[$copy['index_title'], '/news']]),
        ]);
    }

    public function tag(Request $request, string $tag): Response
    {
        $row = DemoNews::tag($tag) ?? abort(404);
        $copy = DemoNews::copy();
        $fill = fn (string $text): string => str_replace('{tag}', $row['name'], $text);

        return $this->archive($request, $this->pageNumber($request), [
            'kind' => 'tag',
            'base' => $row['url'],
            'title' => $fill($copy['tag_title']),
            'intro' => $fill($copy['tag_intro']),
            'description' => $fill($copy['tag_description']),
            'active' => null,
            'posts' => DemoNews::withTag(DemoNews::published(), $tag),
            'featured' => null,
            // PG-13: a thin tag page stays out of the index but its links are followed.
            'indexable' => $row['count'] >= DemoNews::TAG_INDEX_MIN,
            'breadcrumbs' => Breadcrumbs::to($fill($copy['tag_title']), [[$copy['index_title'], '/news']]),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $post = DemoNews::find($slug) ?? abort(404);
        $article = DemoNews::article($post);
        $copy = DemoNews::copy();

        // The cover is the page's LCP image (article-cover preset, HERO-012).
        $seo = SeoData::page($post['title'], $post['excerpt'])
            ->image(DemoNews::ogImage($post), $article['cover']['alt'] ?? null)
            ->lcp($article['cover'], null, '(min-width:1024px) 960px, 100vw')
            ->article();

        return Inertia::render('Site/NewsArticle', [
            'article' => $article,
            'related' => DemoNews::related($post),
            'share' => DemoNews::share($post),
            'breadcrumbs' => Breadcrumbs::to($post['title'], [
                [$copy['index_title'], '/news'],
                [$article['category']['name'], $article['category']['url']],
            ]),
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    /**
     * @param  array{kind: string, base: string, title: string, intro: string, description: string, active: string|null, posts: list<Post>, featured: Post|null, indexable: bool, breadcrumbs: list<array{label: string, url: string|null}>}  $archive
     */
    private function archive(Request $request, int $page, array $archive): Response
    {
        $copy = DemoNews::copy();
        $total = count($archive['posts']);
        $lastPage = max(1, (int) ceil($total / DemoNews::PER_PAGE));

        abort_if($page > $lastPage, 404);

        $posts = array_map(
            DemoNews::card(...),
            array_slice($archive['posts'], ($page - 1) * DemoNews::PER_PAGE, DemoNews::PER_PAGE),
        );
        $featured = $archive['featured'] === null ? null : DemoNews::card($archive['featured']);
        $lead = $featured['cover'] ?? $posts[0]['cover'] ?? null;

        $title = $archive['kind'] === 'index' ? $copy['index_title'] : $archive['title'];

        $seo = SeoData::page($page > 1 ? "{$title} – Page {$page}" : $title, $archive['description'])
            ->lcp($lead, null, '(min-width:1280px) 720px, (min-width:1024px) 58vw, 92vw');

        if (! $archive['indexable']) {
            $seo->noindex();
        }

        $all = count(DemoNews::published());

        return Inertia::render('Site/News', [
            'news' => [
                'kind' => $archive['kind'],
                'title' => $archive['title'],
                'intro' => $archive['intro'],
                'breadcrumbs' => $archive['breadcrumbs'],
                'active_category' => $archive['active'],
                'categories' => [
                    ['slug' => null, 'name' => $copy['all_label'], 'url' => '/news', 'count' => $all],
                    ...array_map(
                        fn (array $category): array => ['slug' => $category['slug'], 'name' => $category['name'], 'url' => $category['url'], 'count' => $category['count']],
                        DemoNews::categories(),
                    ),
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
