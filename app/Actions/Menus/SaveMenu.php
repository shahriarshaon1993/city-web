<?php

namespace App\Actions\Menus;

use App\Actions\Activity\LogActivity;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\Menus\LinkType;
use App\Support\Menus\MegaColumns;
use App\Support\Menus\MenuStore;
use Illuminate\Support\Facades\DB;

/**
 * CMS-NAV-005: saves a whole menu in one go: its name and the item tree, with every item's place
 * (`parent_id`, `sort_order`). Items that are not in the tree any more are deleted (the editor lets
 * the user undo that until Save, crud.md §6), items with an ID are updated, items without one are
 * created. Shape and limits are the Form Request's job; this Action stores what it is given.
 */
final class SaveMenu
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<array<string, mixed>>  $items  the tree, children nested
     * @return array{added: int, updated: int, removed: int, renamed: bool} all zero and false when nothing changed
     */
    public function handle(?User $actor, Menu $menu, string $name, array $items): array
    {
        $result = DB::transaction(function () use ($actor, $menu, $name, $items): array {
            $menu = Menu::query()->whereKey($menu->id)->lockForUpdate()->firstOrFail();
            $existing = MenuItem::query()->where('menu_id', $menu->id)->get()->keyBy('id');
            $kept = [];
            $counts = ['added' => 0, 'updated' => 0, 'removed' => 0];
            $supportsMega = $menu->menuKey()->supportsMega();

            $store = function (array $nodes, ?int $parentId) use (&$store, $menu, $existing, &$kept, &$counts, $supportsMega): void {
                foreach (array_values($nodes) as $position => $node) {
                    $attributes = [...$this->attributes($node, $supportsMega), 'menu_id' => $menu->id, 'parent_id' => $parentId, 'sort_order' => $position];
                    $row = isset($node['id']) ? $existing->get((int) $node['id']) : null;

                    if ($row === null) {
                        $row = MenuItem::query()->create($attributes);
                        $counts['added']++;
                    } else {
                        $row->fill($attributes);

                        if ($row->isDirty()) {
                            $row->save();
                            $counts['updated']++;
                        }
                    }

                    $kept[] = $row->id;
                    $store($node['children'] ?? [], $row->id);
                }
            };

            $store($items, null);

            $gone = $existing->keys()->diff($kept)->all();
            $counts['removed'] = count($gone);

            if ($gone !== []) {
                MenuItem::query()->whereIn('id', $gone)->delete();
            }

            $renamed = $menu->name !== $name;

            if ($renamed) {
                $menu->name = $name;
            }

            $result = [...$counts, 'renamed' => $renamed];

            if (self::changed($result)) {
                $renamed ? $menu->save() : $menu->touch();

                $this->logActivity->handle('menus', 'updated', "Menu saved: {$menu->name}", $actor, $menu, ['menu' => $menu->key, ...$result]);
            }

            return $result;
        });

        if (self::changed($result)) {
            MenuStore::bump();
        }

        return $result;
    }

    /**
     * @param  array{added: int, updated: int, removed: int, renamed: bool}  $result
     */
    public static function changed(array $result): bool
    {
        return $result['added'] + $result['updated'] + $result['removed'] > 0 || $result['renamed'];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function attributes(array $node, bool $supportsMega): array
    {
        $type = LinkType::from($node['link_type']);
        $mega = $supportsMega && ! empty($node['is_mega']);

        return [
            'label' => trim((string) $node['label']),
            'link_type' => $type->value,
            'linkable_type' => $type->isEntity() ? $type->value : null,
            'linkable_id' => $type->isEntity() ? (int) $node['linkable_id'] : null,
            'url' => $type->isEntity() ? null : $this->clean($node['url'] ?? null),
            'anchor' => $type === LinkType::Anchor ? ltrim((string) $this->clean($node['anchor'] ?? null), '#') : null,
            'new_tab' => (bool) ($node['new_tab'] ?? false),
            'icon' => $this->clean($node['icon'] ?? null),
            'description' => $this->clean($node['description'] ?? null),
            'is_mega' => $mega,
            'mega_columns' => $mega ? $this->columns($node['mega_columns'] ?? []) : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    private function columns(array $columns): array
    {
        $out = [];

        foreach (array_slice($columns, 0, MegaColumns::MAX_COLUMNS) as $column) {
            $kind = $column['kind'];
            $links = [];

            if ($kind === 'links') {
                foreach (array_slice(array_values($column['items'] ?? []), 0, MegaColumns::MAX_LINKS) as $link) {
                    $type = LinkType::from($link['link_type']);
                    $links[] = [
                        'label' => trim((string) $link['label']),
                        'link_type' => $type->value,
                        'linkable_id' => $type->isEntity() ? (int) $link['linkable_id'] : null,
                        'url' => $type->isEntity() ? null : $this->clean($link['url'] ?? null),
                        'anchor' => $type === LinkType::Anchor ? ltrim((string) $this->clean($link['anchor'] ?? null), '#') : null,
                        'new_tab' => (bool) ($link['new_tab'] ?? false),
                    ];
                }
            }

            $out[] = ['kind' => $kind, 'heading' => $this->clean($column['heading'] ?? null), 'items' => $links];
        }

        return $out;
    }

    private function clean(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
