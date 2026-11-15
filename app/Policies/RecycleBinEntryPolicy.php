<?php

namespace App\Policies;

use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Support\Auth\SystemRole;
use App\Support\RecycleBin\BinTypes;

/**
 * DEL-07: Restore needs the delete permission of the entry's type (spec/admin.md §10.2);
 * Delete forever is Admin and above on top of that.
 */
class RecycleBinEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return BinTypes::typesFor($user) !== [];
    }

    public function restore(User $user, RecycleBinEntry $entry): bool
    {
        return in_array($entry->type, BinTypes::typesFor($user), true);
    }

    public function forceDelete(User $user, RecycleBinEntry $entry): bool
    {
        return $this->restore($user, $entry)
            && $user->hasAnyRole([SystemRole::SuperAdmin->value, SystemRole::Admin->value]);
    }
}
