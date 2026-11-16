<?php

namespace App\Queries\Admin;

use App\Models\Menu;
use App\Support\Menus\LinkTarget;
use App\Support\Menus\LinkType;
use App\Support\Menus\MenuIcons;
use App\Support\Menus\MenuKey;
use App\Support\Menus\MenuLinks;
use App\Support\Menus\MenuStore;

/**
 * Read side of the menu editor (ADM-SCR-12): the tabs and the tree of one menu with each link
 * resolved the way the site resolves it, so an item that points at something unpublished or gone is
 * flagged here while it is hidden there (CMS-NAV-004).
 */
final class MenuEditor
{
    /**
     * One tab per menu, with how many of its links the public site hides.
     *
     * @return list<array{key: string, label: string, href: string, hidden: int}>
     */
    public static function tabs(): array
    {
        $names = Menu::query()->pluck('name', 'key');
        $tabs = [];

        foreach (MenuKey::cases() as $key) {
            $name = $names[$key->value] ?? $key->defaultName();

            $tabs[] = [
                'key' => $key->value,
                'label' => $key->isFooterColumn() ? "Footer · {$name}" : $name,
                'href' => route('admin.menus.index', ['menu' => $key->value]),
                'hidden' => self::countHidden(MenuStore::tree($key)),
            ];
        }

        return $tabs;
    }

    /**
     * @return array<string, mixed>
     */
    public static function menu(Menu $menu): array
    {
        $key = $menu->menuKey();

        return [
            'key' => $key->value,
            'name' => $menu->name,
            'appears_in' => $key->appearsIn(),
            'is_footer_column' => $key->isFooterColumn(),
            'supports_mega' => $key->supportsMega(),
            'max_depth' => $key->maxDepth(),
            'max_items' => $key->maxItems(),
            'max_children' => $key->maxChildren(),
            'items' => array_map(self::item(...), MenuStore::fresh($menu)),
        ];
    }

    /**
     * Option lists the dialogs need.
     *
     * @return array{link_types: list<array{value: string, label: string, entity: bool}>, icons: list<string>}
     */
    public static function options(): array
    {
        return [
            'link_types' => array_map(
                fn (LinkType $type): array => ['value' => $type->value, 'label' => $type->label(), 'entity' => $type->isEntity()],
                LinkType::cases(),
            ),
            'icons' => MenuIcons::NAMES,
        ];
    }

    /**
     * @param  array<string, mixed>  $item  a stored item
     * @return array<string, mixed> the item plus `link` (state of its target) and described columns
     */
    private static function item(array $item): array
    {
        return [
            ...$item,
            'link' => self::link($item),
            'children' => array_map(self::item(...), $item['children']),
            'mega_columns' => array_map(fn (array $column): array => [
                ...$column,
                'items' => array_map(fn (array $link): array => [...$link, 'link' => self::link($link)], $column['items'] ?? []),
            ], $item['mega_columns']),
        ];
    }

    /**
     * @param  array<string, mixed>  $link
     * @return array{status: string, url: string|null, target: array{id: int, label: string, url: string, published: bool, hint: string|null}|null}
     */
    public static function link(array $link): array
    {
        $resolved = MenuLinks::resolve($link);

        return [
            'status' => $resolved['status'],
            'url' => $resolved['url'],
            'target' => $resolved['target'] instanceof LinkTarget ? $resolved['target']->toArray() : null,
        ];
    }

    /**
     * Links the public site hides, anywhere in a tree (children and mega links included).
     *
     * @param  list<array<string, mixed>>  $items
     */
    private static function countHidden(array $items): int
    {
        $hidden = 0;

        foreach ($items as $item) {
            $hidden += MenuLinks::resolve($item)['status'] === MenuLinks::OK ? 0 : 1;
            $hidden += self::countHidden($item['children']);

            foreach ($item['mega_columns'] as $column) {
                foreach ($column['items'] ?? [] as $link) {
                    $hidden += MenuLinks::resolve($link)['status'] === MenuLinks::OK ? 0 : 1;
                }
            }
        }

        return $hidden;
    }
}
