<?php

namespace App\Policies;

use App\Models\JobPost;
use App\Models\User;

/**
 * Job posts (matrix §10.2): `jobs.view` reads (Viewer), `jobs.manage` creates, edits, closes and deletes
 * (Admin, Content Editor, or an HR role made by Super Admin). Delete forever is limited by the bin itself.
 */
class JobPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('jobs.view');
    }

    public function view(User $user, JobPost $job): bool
    {
        return $user->can('jobs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('jobs.manage');
    }

    public function update(User $user, JobPost $job): bool
    {
        return $user->can('jobs.manage');
    }

    public function delete(User $user, JobPost $job): bool
    {
        return $user->can('jobs.manage');
    }
}
