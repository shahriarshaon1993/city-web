<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

/** Tags: `news.view` reads, `news.manage` renames, merges and deletes (matrix §10.2). */
class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('news.manage');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('news.manage');
    }
}
