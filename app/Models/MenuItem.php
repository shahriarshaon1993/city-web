<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DM-MENU `menu_items`: a link, with its place in the tree (`parent_id`, `sort_order`). Written
 * only by `SaveMenu`. `mega_columns` holds the CMS-NAV-003 columns of a header item.
 *
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string $link_type
 * @property string|null $linkable_type
 * @property int|null $linkable_id
 * @property string|null $url
 * @property string|null $anchor
 * @property bool $new_tab
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_mega
 * @property list<array<string, mixed>>|null $mega_columns
 * @property int $sort_order
 */
#[Fillable([
    'menu_id', 'parent_id', 'label', 'link_type', 'linkable_type', 'linkable_id', 'url', 'anchor',
    'new_tab', 'icon', 'description', 'is_mega', 'mega_columns', 'sort_order',
])]
class MenuItem extends Model
{
    public const int LABEL_MAX = 30;

    public const int DESCRIPTION_MAX = 80;

    public const int URL_MAX = 500;

    public const int ANCHOR_MAX = 42;

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'new_tab' => 'boolean',
            'is_mega' => 'boolean',
            'mega_columns' => 'array',
        ];
    }
}
