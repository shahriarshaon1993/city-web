<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

/** Partners: `social.view` reads, `social.manage` writes (matrix §10.2). */
class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->can('social.manage');
    }
}
