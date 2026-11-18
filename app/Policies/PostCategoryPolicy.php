<?php

namespace App\Policies;

use App\Models\PostCategory;
use App\Models\User;

/** News categories: `news.view` reads, `news.manage` writes (matrix §10.2). */
class PostCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function create(User $user): bool
    {
        return $user->can('news.manage');
    }

    public function update(User $user, PostCategory $category): bool
    {
        return $user->can('news.manage');
    }

    public function delete(User $user, PostCategory $category): bool
    {
        return $user->can('news.manage');
    }
}
