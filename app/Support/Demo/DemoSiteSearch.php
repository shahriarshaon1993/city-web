<?php

namespace App\Support\Demo;

/**
 * Part A site search (PG-19, FR-SRCH-010 result grouping): every word of the
 * query must appear in a record; title matches rank first. Projects search
 * name, tagline, area, city and overview; posts title, excerpt and body;
 * pages title and meta description. At most 10 results per group. 17.2
 * replaces this with Scout's database engine (MySQL FULLTEXT); the result
 * shapes stay (types/search-page.ts).
 */
final class DemoSiteSearch
{
    /** FR-SRCH-010. */
    public const int MIN_LENGTH = 2;

    public const int MAX_LENGTH = 100;

    public const int PER_GROUP = 10;

    /** Builder and custom pages of DemoPages, with their URLs. */
    private const array PAGES = [
        'about' => '/about',
        'leadership' => '/about/leadership',
        'services' => '/services',
        'landowner' => '/landowner',
        'nrb' => '/nrb',
        'sustainability' => '/sustainability',
        'buyer-guide' => '/buyer-guide',
    ];

    /**
     * Grouped results (Projects, News, Pages); empty groups are left out.
     *
     * @return list<array{key: string, heading: string, total: int, items: list<array<string, mixed>>, see_all: array{label: string, url: string}|null}>
     */
    public static function search(string $query): array
    {
        $words = self::words($query);

        $projects = self::rank(array_map(fn (array $row): array => [
            'title' => $row['name'],
            'text' => implode(' ', [$row['tagline'], DemoProjects::area($row['area'])['name'], DemoProjects::area($row['area'])['city'], DemoProjects::SEGMENT_LABELS[$row['segment']], DemoProjectDetail::overviewText($row)]),
            'item' => DemoProjects::card($row),
        ], DemoProjects::rows()), $words);

        $posts = self::rank(array_map(fn (array $post): array => [
            'title' => $post['title'],
            'text' => $post['excerpt'].' '.strip_tags(DemoNews::article($post)['body']),
            'item' => DemoNews::card($post),
        ], DemoNews::published()), $words);

        $pages = self::rank(self::pages(), $words);

        $copy = self::copy();
        $groups = [
            ['key' => 'projects', 'heading' => $copy['projects_heading'], 'total' => count($projects), 'items' => array_slice($projects, 0, self::PER_GROUP), 'see_all' => ['label' => $copy['projects_see_all'], 'url' => '/projects?'.http_build_query(['q' => $query])]],
            ['key' => 'news', 'heading' => $copy['news_heading'], 'total' => count($posts), 'items' => array_slice($posts, 0, self::PER_GROUP), 'see_all' => ['label' => $copy['news_see_all'], 'url' => '/news']],
            ['key' => 'pages', 'heading' => $copy['pages_heading'], 'total' => count($pages), 'items' => array_slice($pages, 0, self::PER_GROUP), 'see_all' => null],
        ];

        return array_values(array_filter($groups, fn (array $group): bool => $group['total'] > 0));
    }

    /**
     * Labels and copy of PG-19 (CMS-000a).
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'title' => 'Search',
            'results_title' => 'Results for “{q}”',
            'description' => 'Search Gemcon City projects, news and pages.',
            'label' => 'Search the site',
            'placeholder' => 'Projects, areas, news…',
            'submit' => 'Search',
            'min_length' => 'Enter at least 2 characters.',
            'summary' => '{n} results for “{q}”',
            'summary_one' => '1 result for “{q}”',
            'projects_heading' => 'Projects',
            'projects_see_all' => 'See all in projects',
            'news_heading' => 'News',
            'news_see_all' => 'All news',
            'pages_heading' => 'Pages',
            'empty_title' => 'No results for “{q}”',
            'empty_body' => 'Check the spelling, or try a broader word such as an area name.',
            'suggestions_heading' => 'Popular areas',
            'suggestion_count' => '{n} projects',
        ];
    }

    /**
     * Popular areas for the empty state (LAY-SEARCH).
     *
     * @return list<array{name: string, city: string, url: string, count: int}>
     */
    public static function suggestions(): array
    {
        return array_map(
            fn (array $area): array => ['name' => $area['name'], 'city' => $area['city'], 'url' => '/locations/'.$area['slug'], 'count' => $area['count']],
            DemoProjects::topAreas(6),
        );
    }

    /**
     * @return list<string>
     */
    private static function words(string $query): array
    {
        return array_values(array_filter(preg_split('/\s+/u', mb_strtolower(trim($query))) ?: [], fn (string $word): bool => $word !== ''));
    }

    /**
     * Records where every word matches, title matches first, else in source order.
     *
     * @param  list<array{title: string, text: string, item: array<string, mixed>}>  $records
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private static function rank(array $records, array $words): array
    {
        if ($words === []) {
            return [];
        }

        $hits = [];

        foreach ($records as $index => $record) {
            $title = mb_strtolower($record['title']);
            $haystack = $title.' '.mb_strtolower($record['text']);

            if (array_any($words, fn (string $word): bool => ! str_contains($haystack, $word))) {
                continue;
            }

            $score = count(array_filter($words, fn (string $word): bool => str_contains($title, $word)));
            $hits[] = ['score' => $score, 'index' => $index, 'item' => $record['item']];
        }

        usort($hits, fn (array $a, array $b): int => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);

        return array_column($hits, 'item');
    }

    /**
     * Pages are searched by title and meta description (FR-SRCH-010).
     *
     * @return list<array{title: string, text: string, item: array<string, mixed>}>
     */
    private static function pages(): array
    {
        $pages = [];

        foreach (self::PAGES as $key => $url) {
            $page = DemoPages::builder($key);

            if ($page !== null) {
                $pages[] = ['title' => $page['title'], 'description' => $page['description'], 'url' => $url];
            }
        }

        foreach (DemoPages::LEGAL as $key) {
            $page = DemoPages::legal($key);

            if ($page !== null) {
                $pages[] = ['title' => $page['title'], 'description' => $page['description'], 'url' => '/'.$key];
            }
        }

        $careers = DemoCareers::copy();
        $pages[] = ['title' => $careers['title'], 'description' => $careers['description'], 'url' => '/careers'];

        return array_values(array_map(fn (array $page): array => [
            'title' => $page['title'],
            'text' => $page['description'],
            'item' => $page,
        ], $pages));
    }
}
