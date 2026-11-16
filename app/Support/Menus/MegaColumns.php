<?php

namespace App\Support\Menus;

use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoProjects;

/**
 * CMS-NAV-003: the columns of a mega menu. A column is a hand-made link list or one of three that
 * the site fills itself: the four project types, the top 8 areas by project count, or 3 featured
 * projects. Hidden links drop out; a column left empty is not rendered.
 */
final class MegaColumns
{
    public const array KINDS = ['links', 'segments', 'locations', 'featured_projects'];

    public const int MAX_COLUMNS = 4;

    public const int MAX_LINKS = 8;

    public const int HEADING_MAX = 30;

    private const array SEGMENT_ICONS = [
        'residential' => 'house',
        'commercial' => 'building',
        'mixed_use' => 'layers',
        'land' => 'land-plot',
    ];

    /**
     * @param  list<array<string, mixed>>  $columns  stored columns
     * @return list<array<string, mixed>> `MegaColumn` shapes of types/site.ts
     */
    public static function resolve(array $columns): array
    {
        $out = [];

        foreach (array_slice($columns, 0, self::MAX_COLUMNS) as $column) {
            $heading = ($column['heading'] ?? null) ?: null;

            $resolved = match ($column['kind'] ?? null) {
                'links' => ['kind' => 'links', 'heading' => $heading, 'items' => self::links($column['items'] ?? [])],
                'segments' => ['kind' => 'segments', 'heading' => $heading, 'items' => self::segments()],
                'locations' => ['kind' => 'locations', 'heading' => $heading, 'items' => self::locations()],
                'featured_projects' => ['kind' => 'featured_projects', 'heading' => $heading, 'projects' => array_slice(DemoContent::projectCards(featuredOnly: true), 0, 3)],
                default => null,
            };

            if ($resolved !== null && ($resolved['items'] ?? $resolved['projects']) !== []) {
                $out[] = $resolved;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $links
     * @return list<array<string, mixed>>
     */
    private static function links(array $links): array
    {
        $items = [];

        foreach ($links as $index => $link) {
            $resolved = MenuLinks::resolve($link);

            if ($resolved['status'] !== MenuLinks::OK) {
                continue;
            }

            $items[] = PublicMenus::item(
                id: -1 - $index,
                label: (string) $link['label'],
                url: (string) $resolved['url'],
                newTab: (bool) ($link['new_tab'] ?? false),
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function segments(): array
    {
        $items = [];

        foreach (DemoProjects::SEGMENT_LABELS as $segment => $label) {
            $items[] = PublicMenus::item(
                id: -100 - count($items),
                label: $label,
                url: '/projects/type/'.str_replace('_', '-', $segment),
                description: DemoContent::SEGMENT_DESCRIPTIONS[$segment],
                icon: self::SEGMENT_ICONS[$segment],
            );
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function locations(): array
    {
        $order = array_column(DemoProjects::areas(), 'slug');

        return array_map(
            fn (array $area): array => PublicMenus::item(
                id: -200 - (int) array_search($area['slug'], $order, true),
                label: $area['name'],
                url: '/locations/'.$area['slug'],
                count: $area['count'],
            ),
            DemoProjects::topAreas(),
        );
    }
}
