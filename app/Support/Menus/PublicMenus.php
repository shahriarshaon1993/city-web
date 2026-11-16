<?php

namespace App\Support\Menus;

/**
 * The `menus` and `footer.columns` shared props (ARC-003, `MenuItem` in types/site.ts): the stored
 * trees with links resolved now, unpublished or missing targets left out, and the header's mega
 * columns filled in.
 */
final class PublicMenus
{
    /**
     * @return array<string, list<array<string, mixed>>> keyed by CMS-NAV-001 menu key
     */
    public static function all(): array
    {
        $menus = [];

        foreach (MenuKey::cases() as $key) {
            $menus[$key->value] = self::items(MenuStore::tree($key), $key);
        }

        return $menus;
    }

    /**
     * Footer column headings are the menu names (DEC-41).
     *
     * @return list<array{menu: string, heading: string}>
     */
    public static function footerColumns(): array
    {
        $columns = [];

        foreach (MenuKey::cases() as $key) {
            if ($key->isFooterColumn()) {
                $columns[] = ['menu' => $key->value, 'heading' => MenuStore::name($key)];
            }
        }

        return $columns;
    }

    /**
     * A public menu item (also used by the auto mega columns and the footer's area list).
     *
     * @return array{id: int, label: string, url: string, new_tab: bool, icon: string|null, description: string|null, count: int|null, children: list<array<string, mixed>>, mega: list<array<string, mixed>>|null}
     */
    public static function item(int $id, string $label, string $url, bool $newTab = false, ?string $icon = null, ?string $description = null, ?int $count = null): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'url' => $url,
            'new_tab' => $newTab,
            'icon' => $icon,
            'description' => $description,
            'count' => $count,
            'children' => [],
            'mega' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function items(array $rows, MenuKey $key, int $depth = 1): array
    {
        $items = [];

        foreach ($rows as $row) {
            $link = MenuLinks::resolve($row);

            if ($link['status'] !== MenuLinks::OK) {
                continue;
            }

            $item = self::item($row['id'], $row['label'], (string) $link['url'], $row['new_tab'], $row['icon'], $row['description']);

            if ($depth < $key->maxDepth()) {
                $item['children'] = self::items($row['children'], $key, $depth + 1);
            }

            if ($key->supportsMega() && $row['is_mega']) {
                $columns = MegaColumns::resolve($row['mega_columns']);
                $item['mega'] = $columns === [] ? null : $columns;
            }

            $items[] = $item;
        }

        return $items;
    }
}
