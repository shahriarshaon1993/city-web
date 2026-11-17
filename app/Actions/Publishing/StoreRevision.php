<?php

namespace App\Actions\Publishing;

use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * DM-REV: writes a revision of an entity. A `published` one is the history entry of a publish; the
 * last 30 per entity are kept (CMS-PUB-004). A `pending` one is the entity's single waiting copy of the
 * editor form (CMS-PUB-002): saving again updates it in place, keeping its schedule.
 */
final class StoreRevision
{
    /** CMS-PUB-004. */
    public const int KEEP = 30;

    /**
     * @param  array<string, mixed>  $payload  the editor form in its canonical shape
     */
    public function handle(Model&PublishableEntity $entity, string $kind, array $payload, ?User $author, ?string $note = null, ?CarbonInterface $publishAt = null): Revision
    {
        $revision = $kind === Revision::PENDING ? $this->pending($entity) : null;
        $revision ??= new Revision(['kind' => $kind]);

        $revision->fill([
            'payload' => $payload,
            'note' => $note,
            'created_by' => $author?->id,
            'publish_at' => $kind === Revision::PENDING ? ($publishAt ?? $revision->publish_at) : null,
        ]);
        $revision->created_at = now();
        $revision->revisionable()->associate($entity);
        $revision->save();

        if ($kind === Revision::PUBLISHED) {
            $this->prune($entity);
        }

        return $revision;
    }

    private function pending(Model&PublishableEntity $entity): ?Revision
    {
        return $entity->pendingRevisionOrNull();
    }

    private function prune(Model&PublishableEntity $entity): void
    {
        $old = Revision::query()
            ->where('revisionable_type', $entity->getMorphClass())
            ->where('revisionable_id', $entity->getKey())
            ->where('kind', Revision::PUBLISHED)
            ->orderByDesc('id')
            ->skip(self::KEEP)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($old->isNotEmpty()) {
            Revision::query()->whereKey($old)->delete();
        }
    }
}
