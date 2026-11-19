<?php

namespace App\Policies;

use App\Models\ImportRun;
use App\Models\User;

/**
 * An import run holds a person's file and its report, so it belongs to whoever started it; an Admin
 * (who manages users) may look at any run but only the starter may change one.
 */
class ImportRunPolicy
{
    public function view(User $user, ImportRun $run): bool
    {
        return $run->user_id === $user->id || $user->can('users.manage');
    }

    public function update(User $user, ImportRun $run): bool
    {
        return $run->user_id === $user->id && $run->type->canBeUsedBy($user);
    }
}
