<?php

namespace App\Policies;

use App\Models\Redirect;
use App\Models\User;

/**
 * Redirects (spec/admin.md §10.2 SEO row): `redirects.manage` (Content Editor and up) creates,
 * edits and deletes; a Viewer's `seo.view` opens the list read-only.
 */
class RedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['redirects.manage', 'seo.view']);
    }

    public function create(User $user): bool
    {
        return $user->can('redirects.manage');
    }

    public function update(User $user, Redirect $redirect): bool
    {
        return $user->can('redirects.manage');
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $user->can('redirects.manage');
    }
}
