<?php

namespace App\Policies;

use App\Models\SavedTableView;
use App\Models\User;
use App\Queries\Admin\AdminTables;

/**
 * ADM-TBL-010 / crud.md Saved views: anyone who can open a table saves personal views for it;
 * sharing needs `views.share`; a view is changed or deleted only by its owner.
 */
class SavedTableViewPolicy
{
    public function create(User $user, string $tableKey): bool
    {
        return AdminTables::find($tableKey)?->isVisibleTo($user) ?? false;
    }

    public function share(User $user): bool
    {
        return $user->can('views.share');
    }

    public function update(User $user, SavedTableView $view): bool
    {
        return $view->user_id === $user->getKey();
    }

    public function delete(User $user, SavedTableView $view): bool
    {
        return $view->user_id === $user->getKey();
    }
}
