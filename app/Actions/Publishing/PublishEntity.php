<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Events\EntityPublished;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * AC-PROJ-03 / CMS-PUB-002/003: "Publish" of a draft or scheduled entity, "Publish changes" of a
 * published one, and what the scheduler calls when the time has come (`$actor` null).
 *
 * The pending revision, if there is one, is applied to the live row first; refused — naming every
 * unmet rule — while the publish checklist of what is about to go live has open items (VAL-05).
 * Every publish stores a revision (CMS-PUB-004), writes one audit entry and dispatches
 * `EntityPublished` once the transaction has committed. All or nothing: a failure leaves the live
 * version and the pending revision as they were.
 */
final class PublishEntity
{
    public function __construct(
        private readonly StoreRevision $storeRevision,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @throws ValidationException when the checklist has open items or a file the changes use is gone
     */
    public function handle(?User $actor, Model&PublishableEntity $entity): Model&PublishableEntity
    {
        $firstPublish = false;

        DB::transaction(function () use ($actor, $entity, &$firstPublish): void {
            $fresh = $entity::query()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();
            $driver = Publishing::for($fresh);
            $pending = $fresh->pendingRevisionOrNull();
            $wasLive = $fresh->getAttribute('publish_status') === PublishStatus::Published;

            if ($wasLive && $pending === null) {
                return;
            }

            $open = array_values(array_filter(
                $driver->checklist($pending === null ? $fresh : $driver->hydrate($fresh, $pending->payload)),
                fn (array $item): bool => ! $item['passed'],
            ));

            if ($open !== []) {
                $labels = implode(', ', array_map(fn (array $item): string => mb_strtolower($item['label']), array_slice($open, 0, 3)));
                $more = count($open) > 3 ? ' and '.(count($open) - 3).' more' : '';

                throw ValidationException::withMessages([
                    'publish' => count($open).' checklist '.(count($open) === 1 ? 'item is' : 'items are').' still open: '.$labels.$more.'.',
                ]);
            }

            $editor = $actor ?? $pending?->author;

            if ($pending !== null) {
                try {
                    $driver->apply($editor, $fresh, $pending->payload, false);
                } catch (QueryException $exception) {
                    // A file or related item the changes point at was deleted after they were saved.
                    report($exception);

                    throw ValidationException::withMessages(['publish' => 'Something these changes use was deleted after they were saved. Open the item, check the highlighted fields and save again.']);
                }
            }

            $publishedAt = $fresh->getAttribute('published_at');
            $firstPublish = ! $wasLive;

            $fresh->forceFill([
                'publish_status' => PublishStatus::Published,
                'published_at' => $publishedAt !== null && $publishedAt->isPast() ? $publishedAt : now(),
                'pending_revision_id' => null,
            ]);
            $fresh->bumpLock($editor);
            $fresh->save();

            $pending?->delete();

            $this->storeRevision->handle(
                $fresh,
                Revision::PUBLISHED,
                $driver->snapshot($fresh->refresh()),
                $editor,
                $wasLive ? 'Changes published' : 'Published',
            );

            $noun = ucfirst($driver->noun());
            $this->logActivity->handle(
                $driver->logName(),
                'published',
                $wasLive ? "{$noun} changes published: {$driver->label($fresh)}" : "{$noun} published: {$driver->label($fresh)}",
                $actor,
                $fresh,
                ['label' => $driver->label($fresh), 'scheduled' => $actor === null],
            );

            DB::afterCommit(fn () => EntityPublished::dispatch($fresh, $firstPublish));
        });

        return $entity->refresh();
    }
}
