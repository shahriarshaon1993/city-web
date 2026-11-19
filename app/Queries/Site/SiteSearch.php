<?php

namespace App\Queries\Site;

use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Support\Careers\CareersCopy;
use App\Support\Site\SearchCopy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * PG-19 / FR-SRCH-010: site search through Scout's database engine (MySQL FULLTEXT, boolean mode) over
 * projects (name, tagline, area and city names, overview), posts (title, excerpt, body) and pages (title,
 * meta description). Every word of the query must match, as a word prefix, so "utt" finds Uttara. Title
 * matches rank first. At most 10 results per group.
 */
final class SiteSearch
{
    /** FR-SRCH-010. */
    public const int MIN_LENGTH = 2;

    public const int MAX_LENGTH = 100;

    public const int PER_GROUP = 10;

    /** Rows pulled per group before ranking; a company site never has more matches than this. */
    private const int POOL = 100;

    /** One line of text from the request: collapsed whitespace, at most MAX_LENGTH characters. */
    public static function normalise(mixed $raw): string
    {
        return Str::limit(is_string($raw) ? trim(preg_replace('/\s+/u', ' ', $raw) ?? '') : '', self::MAX_LENGTH, '');
    }

    /**
     * Grouped results (Projects, News, Pages); empty groups are left out.
     *
     * @return list<array{key: string, heading: string, total: int, items: list<array<string, mixed>>, see_all: array{label: string, url: string}|null}>
     */
    public static function groups(string $query): array
    {
        $words = self::words($query);

        if ($words === []) {
            return [];
        }

        $match = implode(' ', array_map(fn (string $word): string => '+'.$word.'*', $words));
        $copy = SearchCopy::copy();

        $projects = self::rank(
            Project::search($match)->query(fn (Builder $builder) => self::published($builder)->with(ProjectCards::WITH))->take(self::POOL)->get(),
            fn (Project $project): string => $project->name,
            $words,
        );
        $posts = self::rank(
            Post::search($match)->query(fn (Builder $builder) => self::published($builder)->with(NewsQuery::WITH)->orderByDesc('published_at')->orderByDesc('id'))->take(self::POOL)->get(),
            fn (Post $post): string => $post->title,
            $words,
        );
        $pages = self::pages($match, $words);

        $groups = [
            ['key' => 'projects', 'heading' => $copy['projects_heading'], 'total' => count($projects), 'items' => ProjectCards::cards(new Collection(array_slice($projects, 0, self::PER_GROUP))), 'see_all' => ['label' => $copy['projects_see_all'], 'url' => '/projects?'.http_build_query(['q' => $query])]],
            ['key' => 'news', 'heading' => $copy['news_heading'], 'total' => count($posts), 'items' => NewsQuery::resolve(array_slice($posts, 0, self::PER_GROUP)), 'see_all' => ['label' => $copy['news_see_all'], 'url' => '/news']],
            ['key' => 'pages', 'heading' => $copy['pages_heading'], 'total' => count($pages), 'items' => array_slice($pages, 0, self::PER_GROUP), 'see_all' => null],
        ];

        return array_values(array_filter($groups, fn (array $group): bool => $group['total'] > 0));
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
            AreaDirectory::top(6),
        );
    }

    /**
     * The Publishable scope on a Scout query builder (the callback only knows the generic Builder).
     *
     * @param  Builder<Project|Post|Page>  $builder
     * @return Builder<Project|Post|Page>
     */
    private static function published(Builder $builder): Builder
    {
        return $builder->published();
    }

    /**
     * Lower-case letters and digits only: boolean-mode operators in the visitor's text never reach MySQL.
     *
     * @return list<string>
     */
    private static function words(string $query): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query)) ?: [],
            fn (string $word): bool => $word !== '',
        )));
    }

    /**
     * Records whose title holds the most query words first; the order within a score is kept.
     *
     * @template T of Project|Post|Page
     *
     * @param  Collection<int, T>  $records
     * @param  callable(T): string  $title
     * @param  list<string>  $words
     * @return list<T>
     */
    private static function rank(Collection $records, callable $title, array $words): array
    {
        $scored = [];

        foreach ($records->values() as $index => $record) {
            $text = mb_strtolower($title($record));
            $scored[] = ['score' => count(array_filter($words, fn (string $word): bool => str_contains($text, $word))), 'index' => $index, 'record' => $record];
        }

        usort($scored, fn (array $a, array $b): int => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);

        return array_column($scored, 'record');
    }

    /**
     * Published pages except the home page (PG-01), plus the careers page, whose copy is code (CareersCopy).
     *
     * @param  list<string>  $words
     * @return list<array{title: string, description: string, url: string}>
     */
    private static function pages(string $match, array $words): array
    {
        $found = Page::search($match)
            ->query(fn (Builder $builder) => self::published($builder)->whereNotIn('slug', [Page::HOME, 'careers'])->with('seoMeta'))
            ->take(self::POOL)->get();

        $results = array_map(
            fn (Page $page): array => ['title' => $page->title, 'description' => (string) $page->seoMeta?->meta_description, 'url' => $page->path()],
            self::rank($found, fn (Page $page): string => $page->title, $words),
        );

        $careers = CareersCopy::labels();
        $haystack = mb_strtolower($careers['title'].' '.$careers['description']);

        if (array_all($words, fn (string $word): bool => str_contains($haystack, $word))) {
            $results[] = ['title' => $careers['title'], 'description' => $careers['description'], 'url' => '/careers'];
        }

        return $results;
    }
}
