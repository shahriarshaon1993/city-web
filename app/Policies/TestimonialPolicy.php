<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;

/** Testimonials: `social.view` reads, `social.manage` writes (matrix §10.2). */
class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view');
    }

    public function create(User $user): bool
    {
        return $user->can('social.manage');
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->can('social.manage');
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->can('social.manage');
    }
}
