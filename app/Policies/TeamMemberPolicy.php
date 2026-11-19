<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;

/** Team members: `social.view` reads, `social.manage` writes (matrix §10.2). */
class TeamMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, TeamMember $teamMember): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, TeamMember $teamMember): bool
    {
        return $user->can('social.manage');
    }
}
