<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Roles (spec/admin.md §10.2): `roles.manage`, which only a Super Admin holds by default.
 * Registered in AppServiceProvider because Role is a package model.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }
}
