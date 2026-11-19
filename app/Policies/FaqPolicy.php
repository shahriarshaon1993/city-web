<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

/** FAQs: `social.view` reads, `social.manage` writes (matrix §10.2). */
class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, Faq $faq): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, Faq $faq): bool
    {
        return $user->can('social.manage');
    }
}
