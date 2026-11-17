<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CMS-PUB-004: restoring a revision creates a new pending revision with its content (replacing a
 * waiting one); nothing goes live until "Publish changes". The editor then opens with that content.
 */
final class RestoreRevision
{
    public function __construct(
        private readonly StoreRevision $storeRevision,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, Model&PublishableEntity $entity, Revision $revision): Revision
    {
        abort_unless(
            $revision->revisionable_type === $entity->getMorphClass() && $revision->revisionable_id === $entity->getKey(),
            404,
        );

        return DB::transaction(function () use ($actor, $entity, $revision): Revision {
            $driver = Publishing::for($entity);
            $note = 'Restored from '.$revision->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i');
            $pending = $this->storeRevision->handle($entity, Revision::PENDING, $revision->payload, $actor, $note);

            $entity->forceFill(['pending_revision_id' => $pending->id]);
            $entity->bumpLock($actor);
            $entity->saveQuietly();

            $this->logActivity->handle($driver->logName(), 'updated', ucfirst($driver->noun()).' revision restored: '.$driver->label($entity), $actor, $entity, ['label' => $driver->label($entity), 'revision' => $revision->id]);

            return $pending;
        });
    }
}
