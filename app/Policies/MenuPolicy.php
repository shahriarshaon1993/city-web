<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

/**
 * Menus (spec/admin.md §10.2): `menus.view` opens the editor read-only, `menus.manage` saves it.
 */
class MenuPolicy
{
    public function view(User $user, Menu $menu): bool
    {
        return $user->canAny(['menus.view', 'menus.manage']);
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->can('menus.manage');
    }
}
