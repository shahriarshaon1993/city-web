<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Posts (matrix §10.2): `news.view` reads, `news.manage` creates, edits, publishes and deletes.
 * Delete forever is limited by the Recycle bin itself (DEC-76).
 */
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can('news.view');
    }

    public function create(User $user): bool
    {
        return $user->can('news.manage');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('news.manage');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('news.manage');
    }

    /** CMS-PUB: publishing, scheduling and unpublishing. */
    public function publish(User $user, Post $post): bool
    {
        return $user->can('news.manage');
    }
}
