<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use App\Support\Publishing\StaleEntityException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CMS-PUB-002 / CMS-PUB-006: what Save does in an entity editor, and what its 60-second autosave does.
 *
 * - A **published** entity stores the form as its pending revision: the live version stays as it is
 *   until "Publish changes" (`PublishEntity`).
 * - Any other status (draft, scheduled) writes the live row, which is not public anyway; a pending
 *   revision that was waiting there (restored from history, or left by an unpublish) is dropped,
 *   because the editor started from it and its content is in the form being saved.
 *
 * The save is refused with `StaleEntityException` when `lock_version` moved since the editor opened
 * the item, unless `$overwrite` (the Admin-only dialog choice). Every successful save raises the lock.
 */
final class SaveEntityChanges
{
    public function __construct(
        private readonly StoreRevision $storeRevision,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  validated input of the entity's Form Request
     * @param  int|null  $expectedLock  the `lock_version` the editor loaded; null skips the check
     * @param  bool  $autosave  quiet: no activity entry
     * @return bool true when the form went to the pending revision, false when it was written to the live row
     *
     * @throws StaleEntityException
     */
    public function handle(User $actor, Model&PublishableEntity $entity, array $payload, ?int $expectedLock, bool $overwrite = false, bool $autosave = false): bool
    {
        $pending = DB::transaction(function () use ($actor, $entity, $payload, $expectedLock, $overwrite, $autosave): bool {
            $fresh = $entity::query()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();

            if ($expectedLock !== null && (int) $fresh->getAttribute('lock_version') !== $expectedLock && ! $overwrite) {
                $fresh->load('editor');

                throw new StaleEntityException($fresh);
            }

            $driver = Publishing::for($fresh);

            if ($fresh->getAttribute('publish_status') === PublishStatus::Published) {
                $canonical = $driver->snapshot($driver->hydrate($fresh, $payload));
                $revision = $this->storeRevision->handle($fresh, Revision::PENDING, $canonical, $actor);

                $fresh->forceFill(['pending_revision_id' => $revision->id]);
                $fresh->bumpLock($actor);
                $fresh->saveQuietly();

                if (! $autosave) {
                    $this->logActivity->handle($driver->logName(), 'updated', ucfirst($driver->noun()).' changes saved: '.$driver->label($fresh), $actor, $fresh, ['label' => $driver->label($fresh), 'pending' => true]);
                }

                return true;
            }

            $driver->apply($actor, $fresh, $payload, ! $autosave);

            $stale = $fresh->getAttribute('pending_revision_id');

            if ($stale !== null) {
                $fresh->forceFill(['pending_revision_id' => null])->saveQuietly();
                Revision::query()->whereKey($stale)->delete();
            }

            return false;
        });

        $entity->refresh();

        return $pending;
    }
}
