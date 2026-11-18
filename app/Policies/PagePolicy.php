<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

/**
 * Pages (matrix §10.2): builder pages need `pages.view` / `pages.manage`, legal pages their own pair
 * `legal.view` / `legal.manage`. A system page can be edited and published but never deleted
 * (crud.md matrix, DEL-03).
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pages.view') || $user->can('legal.view');
    }

    public function view(User $user, Page $page): bool
    {
        return $user->can($page->isLegal() ? 'legal.view' : 'pages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pages.manage');
    }

    public function update(User $user, Page $page): bool
    {
        return $user->can($page->isLegal() ? 'legal.manage' : 'pages.manage');
    }

    public function delete(User $user, Page $page): bool
    {
        return ! $page->is_system && $this->update($user, $page);
    }

    /** CMS-PUB: publishing, scheduling and unpublishing (the verbs that change what is public). */
    public function publish(User $user, Page $page): bool
    {
        return $this->update($user, $page);
    }
}
