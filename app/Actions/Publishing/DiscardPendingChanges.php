<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Workflow "Discard revision": the pending changes are thrown away and the live version stays. */
final class DiscardPendingChanges
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Model&PublishableEntity $entity): Model&PublishableEntity
    {
        $pendingId = $entity->getAttribute('pending_revision_id');

        if ($pendingId === null) {
            return $entity;
        }

        DB::transaction(function () use ($actor, $entity, $pendingId): void {
            $driver = Publishing::for($entity);

            $entity->forceFill(['pending_revision_id' => null]);
            $entity->bumpLock($actor);
            $entity->saveQuietly();
            Revision::query()->whereKey($pendingId)->delete();

            $this->logActivity->handle($driver->logName(), 'updated', ucfirst($driver->noun()).' changes discarded: '.$driver->label($entity), $actor, $entity, ['label' => $driver->label($entity), 'discarded' => true]);
        });

        return $entity;
    }
}
