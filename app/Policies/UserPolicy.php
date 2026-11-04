<?php

namespace App\Policies;

use App\Models\User;

/**
 * Users & roles (spec/admin.md §10.2): `users.manage` opens the screen and changes users. The
 * limits on whom an Admin may change (Super Admin, yourself, the last Super Admin) are in
 * App\Support\Users\UserGuard, because Gate::before lets a Super Admin skip a Policy.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    /** Bulk actions: no single target, the limits are applied per user by the Action. */
    public function manage(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }
}
