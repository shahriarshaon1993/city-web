<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * ARC-012 / CMS-PUB-003: every minute, publish what is due — scheduled entities whose time has come
 * and pending revisions whose `publish_at` has passed (AC-PROJ-04). Each item stands alone: one that
 * fails (the checklist no longer passes, a file was deleted) is taken off the schedule, recorded in
 * the activity log, and never stops the others or is retried forever.
 */
final class PublishDueEntities
{
    public function __construct(
        private readonly PublishEntity $publishEntity,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @return array{published: int, failed: int}
     */
    public function handle(): array
    {
        $published = 0;
        $failed = 0;

        foreach (Publishing::types() as $type) {
            foreach ($this->due($type) as $entity) {
                if (! $entity instanceof PublishableEntity) {
                    continue;
                }

                try {
                    $this->publishEntity->handle(null, $entity);
                    $published++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->fail($type, $entity, $exception);
                }
            }
        }

        return ['published' => $published, 'failed' => $failed];
    }

    /**
     * @return iterable<Model>
     */
    private function due(string $type): iterable
    {
        $class = Publishing::modelClass($type);

        $scheduled = $class::query()
            ->where('publish_status', PublishStatus::Scheduled->value)
            ->where('published_at', '<=', now())
            ->get();

        $pendingIds = Revision::query()
            ->where('revisionable_type', $type)
            ->where('kind', Revision::PENDING)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->pluck('revisionable_id');

        $pending = $class::query()
            ->whereKey($pendingIds)
            ->where('publish_status', PublishStatus::Published->value)
            ->get();

        return $scheduled->concat($pending);
    }

    private function fail(string $type, Model&PublishableEntity $entity, Throwable $exception): void
    {
        $driver = Publishing::driver($type);
        $reason = $exception instanceof ValidationException ? $exception->validator->errors()->first() : 'An unexpected error stopped the publish.';

        if (! $exception instanceof ValidationException) {
            report($exception);
        }

        // Off the schedule: a draft again, or the pending changes kept without a time.
        if ($entity->getAttribute('publish_status') === PublishStatus::Scheduled) {
            $entity->forceFill(['publish_status' => PublishStatus::Draft, 'published_at' => null])->saveQuietly();
        } elseif ($entity->getAttribute('pending_revision_id') !== null) {
            Revision::query()->whereKey($entity->getAttribute('pending_revision_id'))->update(['publish_at' => null]);
        }

        $this->logActivity->handle(
            $driver->logName(),
            'publish_failed',
            'Scheduled publish failed: '.$driver->label($entity),
            null,
            $entity,
            ['label' => $driver->label($entity), 'reason' => $reason],
        );
    }
}
