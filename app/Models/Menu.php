<?php

namespace App\Models;

use App\Support\Menus\MenuKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DM-MENU: one of the seven fixed menus (CMS-NAV-001). Only its name and items change.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 */
#[Fillable(['name'])]
class Menu extends Model
{
    public const int NAME_MAX = 40;

    /** Admin URLs name a menu by its fixed key: `/admin/menus/footer_1`. */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function menuKey(): MenuKey
    {
        return MenuKey::from($this->key);
    }

    /**
     * Every item of the menu, top level first, in display order.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
