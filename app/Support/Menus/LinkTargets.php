<?php

namespace App\Support\Menus;

use App\Support\Demo\DemoNews;
use App\Support\Demo\DemoPages;
use App\Support\Demo\DemoProjects;
use Illuminate\Support\Carbon;

/**
 * The entities a menu can link to, found by type and ID (CMS-NAV-004). Menus and the admin picker
 * only ever call this class, so the day an entity moves to its table only its method changes:
 * projects and areas in 13.3, pages in 16.1, articles and categories in 16.3. Until then the IDs
 * are those of the demo content (DEC-29), which stay stable on purpose (DemoProjects, DemoNews).
 */
final class LinkTargets
{
    /** @var array<string, list<LinkTarget>> */
    private static array $memo = [];

    /**
     * Targets of one type, optionally narrowed by a search text, newest or first-listed first.
     *
     * @return list<LinkTarget>
     */
    public static function search(LinkType $type, string $query = '', int $limit = 25): array
    {
        $query = mb_strtolower(trim($query));
        $matches = array_filter(
            self::all($type),
            fn (LinkTarget $target): bool => $query === '' || str_contains(mb_strtolower($target->label.' '.$target->hint), $query),
        );

        return array_slice(array_values($matches), 0, $limit);
    }

    public static function find(LinkType $type, int $id): ?LinkTarget
    {
        foreach (self::all($type) as $target) {
            if ($target->id === $id) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @return list<LinkTarget>
     */
    private static function all(LinkType $type): array
    {
        return self::$memo[$type->value] ??= match ($type) {
            LinkType::Project => self::projects(),
            LinkType::Location => self::locations(),
            LinkType::Page => self::pages(),
            LinkType::Post => self::posts(),
            LinkType::Category => self::categories(),
            default => [],
        };
    }

    /** @return list<LinkTarget> */
    private static function projects(): array
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();

        return array_map(
            fn (array $row): LinkTarget => new LinkTarget(
                LinkType::Project,
                $row['id'],
                $row['name'],
                '/projects/'.$row['slug'],
                $row['published_at'] <= $today,
                DemoProjects::area($row['area'])['name'].' · '.DemoProjects::LIFECYCLE_LABELS[$row['lifecycle']],
            ),
            DemoProjects::rows(),
        );
    }

    /** @return list<LinkTarget> */
    private static function locations(): array
    {
        return array_map(
            fn (array $area, int $index): LinkTarget => new LinkTarget(
                LinkType::Location,
                $index + 1,
                $area['name'],
                '/locations/'.$area['slug'],
                // FR-LOC: an area with no published project still has its page (AC-LOC-01).
                true,
                $area['city'],
            ),
            DemoProjects::areas(),
            array_keys(DemoProjects::areas()),
        );
    }

    /** @return list<LinkTarget> */
    private static function pages(): array
    {
        $keys = [...DemoPages::BUILDER, 'sustainability', 'buyer-guide', ...DemoPages::LEGAL];
        $targets = [];

        foreach ($keys as $index => $key) {
            $page = in_array($key, DemoPages::LEGAL, true) ? DemoPages::legal($key) : DemoPages::builder($key);

            if ($page === null) {
                continue;
            }

            $targets[] = new LinkTarget(
                LinkType::Page,
                $index + 1,
                $page['title'],
                $key === 'leadership' ? '/about/leadership' : '/'.$key,
                true,
                in_array($key, DemoPages::LEGAL, true) ? 'Legal page' : 'Page',
            );
        }

        return $targets;
    }

    /** @return list<LinkTarget> */
    private static function posts(): array
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();

        return array_map(
            fn (array $post, int $index): LinkTarget => new LinkTarget(
                LinkType::Post,
                $index + 1,
                $post['title'],
                '/news/'.$post['slug'],
                $post['published_at'] <= $today,
                $post['published_at'] <= $today ? $post['published_at'] : 'Scheduled '.$post['published_at'],
            ),
            DemoNews::all(),
            array_keys(DemoNews::all()),
        );
    }

    /** @return list<LinkTarget> */
    private static function categories(): array
    {
        return array_map(
            fn (array $category, int $index): LinkTarget => new LinkTarget(LinkType::Category, $index + 1, $category['name'], $category['url'], true, $category['count'].' articles'),
            DemoNews::categories(),
            array_keys(DemoNews::categories()),
        );
    }
}
