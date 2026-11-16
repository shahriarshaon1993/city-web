<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/** Cities and areas (matrix §10.2 "Locations, facets, amenities"): `catalog.view` reads, `catalog.manage` writes. */
class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function update(User $user, Location $location): bool
    {
        return $user->can('catalog.manage');
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->can('catalog.manage');
    }
}
