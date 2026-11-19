<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

/** Offices: `social.view` reads, `social.manage` writes (matrix §10.2). */
class OfficePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, Office $office): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, Office $office): bool
    {
        return $user->can('social.manage');
    }
}
