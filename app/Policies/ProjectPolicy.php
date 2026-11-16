<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Projects (spec/projects.md permissions, matrix §10.2): view / write / publish / delete.
 * A Content Editor writes and publishes but cannot delete.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('projects.write');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.write');
    }

    public function publish(User $user, Project $project): bool
    {
        return $user->can('projects.publish');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete');
    }
}
