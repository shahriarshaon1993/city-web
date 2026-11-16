<?php

namespace App\Policies;

use App\Models\FacetPage;
use App\Models\User;

/** Facet landing pages (ADM-SCR-05): fixed rows, so only reading and editing exist. */
class FacetPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.view');
    }

    public function update(User $user, FacetPage $facetPage): bool
    {
        return $user->can('catalog.manage');
    }
}
