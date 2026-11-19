<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

/**
 * Job applications (matrix §10.2): only `applications.manage` (Admin, or an HR role) reads, changes,
 * downloads the CV and deletes. Applications hold personal data, so there is no read-only role.
 */
class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('applications.manage');
    }

    public function view(User $user, JobApplication $application): bool
    {
        return $user->can('applications.manage');
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $user->can('applications.manage');
    }

    public function delete(User $user, JobApplication $application): bool
    {
        return $user->can('applications.manage');
    }
}
