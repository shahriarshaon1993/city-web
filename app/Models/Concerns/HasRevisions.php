<?php

namespace App\Models\Concerns;

use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * CMS-PUB-002/004/006: a publishable entity with a pending revision, a revision history and an
 * optimistic lock. `lock_version` goes up on every change an editor makes (live row or pending
 * revision) and `edited_by` names the last editor, so a stale save can say "Changed by …".
 */
trait HasRevisions
{
    protected function initializeHasRevisions(): void
    {
        $this->mergeCasts(['lock_version' => 'integer']);
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function pendingRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'pending_revision_id');
    }

    /**
     * @return MorphMany<Revision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function pendingRevisionOrNull(): ?Revision
    {
        return $this->pendingRevision()->first();
    }

    /**
     * @return Builder<Revision>
     */
    public function revisionHistory(): Builder
    {
        return $this->revisions()->getQuery();
    }

    /** Raise the lock and record who edited; the caller saves. */
    public function bumpLock(?User $actor): void
    {
        $this->lock_version = (int) $this->lock_version + 1;
        $this->forceFill(['edited_by' => $actor?->id]);
    }
}
