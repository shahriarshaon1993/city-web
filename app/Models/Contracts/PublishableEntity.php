<?php

namespace App\Models\Contracts;

use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A model that goes through the publishing workflow (CMS-PUB): it has a status, a pending revision,
 * a revision history and an optimistic lock. Used with `Model` as an intersection type
 * (`Model&PublishableEntity`) by the Actions in `app/Actions/Publishing`, so they work for every
 * entity that uses `Publishable` and `HasRevisions`.
 */
interface PublishableEntity
{
    /** The one waiting copy of the editor form, if there is one (CMS-PUB-002). */
    public function pendingRevisionOrNull(): ?Revision;

    /**
     * Pending and published revisions of this entity (CMS-PUB-004).
     *
     * @return Builder<Revision>
     */
    public function revisionHistory(): Builder;

    /** Raise `lock_version` and record the editor; the caller saves. */
    public function bumpLock(?User $actor): void;

    /** Published and not dated in the future. */
    public function isPublished(): bool;
}
