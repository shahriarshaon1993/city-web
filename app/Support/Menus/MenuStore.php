<?php

namespace App\Support\Menus;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

/**
 * Read side of the menus: the stored tree of each menu as plain arrays, cached under the versioned
 * key `menu:{key}:v{n}` (CMS-CACHE-002). `SaveMenu` raises the counter. Only what the admin typed is
 * cached; links are resolved on every render (`MenuLinks`), so a slug change never leaves a stale
 * URL behind (CMS-NAV-004).
 *
 * A stored item is `{id, label, link_type, linkable_id, url, anchor, new_tab, icon, description,
 * is_mega, mega_columns, children}`, children nested.
 */
final class MenuStore
{
    private const string VERSION_KEY = 'cachever:menus';

    /** @var array<string, array{name: string, items: list<array<string, mixed>>}> */
    private static array $memo = [];

    /**
     * @return list<array<string, mixed>>
     */
    public static function tree(MenuKey $key): array
    {
        return self::load($key)['items'];
    }

    public static function name(MenuKey $key): string
    {
        return self::load($key)['name'];
    }

    /**
     * The same shape straight from the database, never from the cache (the admin editor).
     *
     * @return list<array<string, mixed>>
     */
    public static function fresh(Menu $menu): array
    {
        $rows = MenuItem::query()->where('menu_id', $menu->id)->orderBy('sort_order')->orderBy('id')->get();

        return self::nest($rows);
    }

    /** Called by `SaveMenu` after a save. */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);

        self::flushMemo();
    }

    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * @return array{name: string, items: list<array<string, mixed>>}
     */
    private static function load(MenuKey $key): array
    {
        return self::$memo[$key->value] ??= Cache::rememberForever("menu:{$key->value}:v".self::version(), function () use ($key): array {
            $menu = Menu::query()->where('key', $key->value)->first();

            return [
                'name' => $menu->name ?? $key->defaultName(),
                'items' => $menu === null ? [] : self::fresh($menu),
            ];
        });
    }

    /**
     * @param  iterable<MenuItem>  $rows  every item of one menu, in display order
     * @return list<array<string, mixed>>
     */
    private static function nest(iterable $rows): array
    {
        $byParent = [];

        foreach ($rows as $row) {
            $byParent[$row->parent_id ?? 0][] = $row;
        }

        $build = function (int $parent) use (&$build, $byParent): array {
            return array_map(fn (MenuItem $row): array => [
                'id' => $row->id,
                'label' => $row->label,
                'link_type' => $row->link_type,
                'linkable_id' => $row->linkable_id,
                'url' => $row->url,
                'anchor' => $row->anchor,
                'new_tab' => $row->new_tab,
                'icon' => $row->icon,
                'description' => $row->description,
                'is_mega' => $row->is_mega,
                'mega_columns' => $row->mega_columns ?? [],
                'children' => $build($row->id),
            ], $byParent[$parent] ?? []);
        };

        return $build(0);
    }
}
