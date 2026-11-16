<?php

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\Menus\LinkTargets;
use App\Support\Menus\LinkType;
use App\Support\Menus\MegaColumns;
use App\Support\Menus\MenuIcons;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * CMS-NAV-002 / 003: one menu with its whole item tree. Field rules are written per level (the
 * header goes two levels deep, mega columns hold link lists); the structure (depth, counts, mega
 * only on top-level header items) and the "does that page still exist" checks run afterwards and
 * report on the exact item, so the editor can open it (VAL-02: plain words, named by position).
 */
class SaveMenuRequest extends FormRequest
{
    private const string URL_PATTERN = '#^(/(?!/)|https?://|mailto:|tel:)#i';

    public function menu(): Menu
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');

        return $menu;
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->menu());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:'.Menu::NAME_MAX],
            'items' => ['present', 'array', 'max:'.$this->menu()->menuKey()->maxItems()],
        ];

        $levels = ['items.*', 'items.*.children.*'];

        foreach ($levels as $prefix) {
            $rules += $this->linkRules($prefix);
            $rules += [
                "{$prefix}.id" => ['nullable', 'integer'],
                "{$prefix}.icon" => ['nullable', 'string', Rule::in(MenuIcons::NAMES)],
                "{$prefix}.description" => ['nullable', 'string', 'max:'.MenuItem::DESCRIPTION_MAX],
                "{$prefix}.is_mega" => ['nullable', 'boolean'],
                "{$prefix}.children" => ['nullable', 'array', 'max:'.$this->menu()->menuKey()->maxChildren()],
                "{$prefix}.mega_columns" => ['nullable', 'array', 'max:'.MegaColumns::MAX_COLUMNS],
                "{$prefix}.mega_columns.*.kind" => ['required', Rule::in(MegaColumns::KINDS)],
                "{$prefix}.mega_columns.*.heading" => ['nullable', 'string', 'max:'.MegaColumns::HEADING_MAX],
                "{$prefix}.mega_columns.*.items" => ['nullable', 'array', 'max:'.MegaColumns::MAX_LINKS],
            ];
            $rules += $this->linkRules("{$prefix}.mega_columns.*.items.*");
        }

        return $rules;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function linkRules(string $prefix): array
    {
        return [
            "{$prefix}.label" => ['required', 'string', 'max:'.MenuItem::LABEL_MAX],
            "{$prefix}.link_type" => ['required', Rule::in(LinkType::values())],
            "{$prefix}.linkable_id" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.url" => ['nullable', 'string', 'max:'.MenuItem::URL_MAX, 'regex:'.self::URL_PATTERN],
            "{$prefix}.anchor" => ['nullable', 'string', 'max:'.MenuItem::ANCHOR_MAX, 'regex:/^#?[A-Za-z][A-Za-z0-9_-]*$/'],
            "{$prefix}.new_tab" => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkTree($validator)];
    }

    private function checkTree(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $key = $this->menu()->menuKey();

        foreach ((array) $this->input('items') as $index => $item) {
            $path = "items.{$index}";

            $this->checkLink($validator, $path, $item);

            $children = (array) ($item['children'] ?? []);

            if ($children !== [] && $key->maxDepth() < 2) {
                $validator->errors()->add("{$path}.children", ucfirst($this->trail($path)).' has sub-items, but this menu is one level deep. Move them up to the main list.');
            }

            if (! empty($item['is_mega'])) {
                $this->checkMega($validator, $path, $item, $children !== []);
            }

            foreach ($children as $childIndex => $child) {
                $childPath = "{$path}.children.{$childIndex}";

                $this->checkLink($validator, $childPath, $child);

                if (! empty($child['children'])) {
                    $validator->errors()->add("{$childPath}.children", ucfirst($this->trail($childPath)).' has sub-items, but menus go two levels deep at most. Move them up one level.');
                }

                if (! empty($child['is_mega'])) {
                    $validator->errors()->add("{$childPath}.is_mega", ucfirst($this->trail($childPath)).' cannot open a mega menu. Only a main header item can.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function checkMega(Validator $validator, string $path, array $item, bool $hasChildren): void
    {
        if (! $this->menu()->menuKey()->supportsMega()) {
            $validator->errors()->add("{$path}.is_mega", ucfirst($this->trail($path)).' cannot open a mega menu. Only the header menu has them.');

            return;
        }

        if ($hasChildren) {
            $validator->errors()->add("{$path}.is_mega", ucfirst($this->trail($path)).' has sub-items. A mega menu replaces them, so remove the sub-items first.');
        }

        if (empty($item['mega_columns'])) {
            $validator->errors()->add("{$path}.mega_columns", ucfirst($this->trail($path)).' has a mega menu without columns. Add a column or switch it off.');
        }

        foreach ((array) ($item['mega_columns'] ?? []) as $columnIndex => $column) {
            foreach ((array) ($column['items'] ?? []) as $linkIndex => $link) {
                $this->checkLink($validator, "{$path}.mega_columns.{$columnIndex}.items.{$linkIndex}", $link);
            }

            if (($column['kind'] ?? null) === 'links' && empty($column['items'])) {
                $validator->errors()->add("{$path}.mega_columns.{$columnIndex}.items", ucfirst($this->trail("{$path}.mega_columns.{$columnIndex}")).' has no links. Add one or remove the column.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $link
     */
    private function checkLink(Validator $validator, string $path, array $link): void
    {
        $type = LinkType::from($link['link_type']);

        if ($type->isEntity()) {
            $id = $link['linkable_id'] ?? null;

            if ($id === null) {
                $validator->errors()->add("{$path}.linkable_id", ucfirst($this->trail($path))." needs a target. Choose which {$this->noun($type)} it links to.");
            } elseif (LinkTargets::find($type, (int) $id) === null) {
                $validator->errors()->add("{$path}.linkable_id", ucfirst($this->trail($path))." links to a {$this->noun($type)} that no longer exists. Choose another.");
            }

            return;
        }

        if ($type === LinkType::Url && blank($link['url'] ?? null)) {
            $validator->errors()->add("{$path}.url", ucfirst($this->trail($path)).' needs an address, e.g. /projects or https://example.com.');
        }

        if ($type === LinkType::Anchor && blank($link['anchor'] ?? null)) {
            $validator->errors()->add("{$path}.anchor", ucfirst($this->trail($path)).' needs a section id, e.g. enquire.');
        }
    }

    private function noun(LinkType $type): string
    {
        return mb_strtolower($type->label());
    }

    /**
     * VAL-02: every field of an item is named by the item's place in the tree, as the editor lists
     * it ("item 2 “About” › sub-item 1"), so a message always says which link it is about.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['name' => 'menu name', 'items' => 'menu'];
        $fields = ['id', 'label', 'link_type', 'linkable_id', 'url', 'anchor', 'new_tab', 'icon', 'description', 'is_mega', 'children', 'mega_columns', 'heading', 'kind', 'items'];

        $visit = function (array $nodes, string $path) use (&$visit, &$attributes, $fields): void {
            foreach (array_values($nodes) as $index => $node) {
                $key = "{$path}.{$index}";
                $trail = $this->trail($key);

                foreach ($fields as $field) {
                    $attributes["{$key}.{$field}"] = $trail;
                }

                if (! is_array($node)) {
                    continue;
                }

                $visit((array) ($node['children'] ?? []), "{$key}.children");
                $visit((array) ($node['mega_columns'] ?? []), "{$key}.mega_columns");
                $visit((array) ($node['items'] ?? []), "{$key}.items");
            }
        };

        $visit((array) $this->input('items'), 'items');

        return $attributes;
    }

    /**
     * "item 2 “Projects” › column 1 › link 3" for the key of a node.
     */
    private function trail(string $nodeKey): string
    {
        $nouns = ['children' => 'sub-item', 'mega_columns' => 'column', 'items' => 'item'];
        $node = $this->all();
        $out = [];
        $noun = 'item';
        $previous = null;

        foreach (explode('.', $nodeKey) as $part) {
            $node = is_array($node) ? ($node[$part] ?? null) : null;

            if (! ctype_digit($part)) {
                $noun = $nouns[$part] ?? $noun;

                if ($part === 'items' && $previous !== null && $previous !== 'items') {
                    $noun = 'link';
                }

                $previous = $part;

                continue;
            }

            $label = is_array($node) && filled($node['label'] ?? null) ? ' “'.$node['label'].'”' : '';
            $out[] = $noun.' '.((int) $part + 1).$label;
        }

        return implode(' › ', $out);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $link = [
            'label.required' => 'Enter a label for :attribute.',
            'label.max' => 'The label for :attribute can be :max characters at most.',
            'link_type.in' => 'Choose what :attribute links to.',
            'linkable_id.integer' => 'Choose what :attribute links to.',
            'url.max' => 'The address for :attribute can be :max characters at most.',
            'url.regex' => 'The address for :attribute must start with / (a page on this site, e.g. /projects) or https://.',
            'anchor.max' => 'The section id for :attribute can be :max characters at most.',
            'anchor.regex' => 'The section id for :attribute uses letters, numbers, - and _ only, starting with a letter (e.g. enquire).',
        ];
        $item = [
            ...$link,
            'icon.in' => 'Choose an icon for :attribute from the list.',
            'description.max' => 'The description for :attribute can be :max characters at most.',
            'children.max' => 'A menu item holds :max sub-items at most (:attribute).',
            'mega_columns.max' => 'A mega menu has :max columns at most (:attribute).',
        ];
        $column = [
            'kind.in' => 'Choose what :attribute shows.',
            'heading.max' => 'The heading for :attribute can be :max characters at most.',
            'items.max' => 'A column holds :max links at most (:attribute).',
        ];

        $messages = [
            'name.required' => 'Give the menu a name.',
            'name.max' => 'The menu name can be :max characters at most.',
            'items.max' => 'This menu holds :max main items at most. Remove one or move it into another menu.',
        ];

        foreach (['items.*', 'items.*.children.*'] as $prefix) {
            foreach ([$prefix => $item, "{$prefix}.mega_columns.*" => $column, "{$prefix}.mega_columns.*.items.*" => $link] as $node => $texts) {
                foreach ($texts as $rule => $text) {
                    $messages["{$node}.{$rule}"] = $text;
                }
            }
        }

        return $messages;
    }
}
