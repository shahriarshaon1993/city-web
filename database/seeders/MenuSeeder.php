<?php

namespace Database\Seeders;

use App\Actions\Menus\SaveMenu;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\Menus\MenuDefaults;
use Illuminate\Database\Seeder;

/**
 * Fills the seven menus with the navigation of the approved frontend (MenuDefaults). A menu that
 * already has items is left alone, so it is safe to run on a site whose menus were edited:
 * `php artisan db:seed --class=MenuSeeder`.
 */
class MenuSeeder extends Seeder
{
    public function run(SaveMenu $saveMenu): void
    {
        foreach (MenuDefaults::items() as $key => $items) {
            $menu = Menu::query()->where('key', $key)->first();

            if ($menu === null || MenuItem::query()->where('menu_id', $menu->id)->exists()) {
                continue;
            }

            $saveMenu->handle(null, $menu, $menu->name, $items);
        }
    }
}
