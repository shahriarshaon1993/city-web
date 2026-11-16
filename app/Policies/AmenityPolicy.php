<?php

namespace App\Policies;

use App\Models\Amenity;
use App\Models\User;

/** Amenities: `catalog.view` reads, `catalog.manage` writes (matrix §10.2). */
class AmenityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function update(User $user, Amenity $amenity): bool
    {
        return $user->can('catalog.manage');
    }

    public function delete(User $user, Amenity $amenity): bool
    {
        return $user->can('catalog.manage');
    }
}
