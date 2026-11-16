<?php

namespace App\Policies;

use App\Models\NotFoundLog;
use App\Models\User;

/**
 * 404 monitor (spec/admin.md §10.2 SEO row): `redirects.manage` ignores, deletes and edits the
 * ignore list; a Viewer's `seo.view` reads the list.
 */
class NotFoundLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['redirects.manage', 'seo.view']);
    }

    public function manage(User $user): bool
    {
        return $user->can('redirects.manage');
    }

    public function update(User $user, NotFoundLog $log): bool
    {
        return $user->can('redirects.manage');
    }

    public function delete(User $user, NotFoundLog $log): bool
    {
        return $user->can('redirects.manage');
    }
}
