<?php

namespace App\Support\Publishing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * What the publishing workflow (CMS-PUB-002…006) needs to know about one publishable entity, so the
 * Actions in `app/Actions/Publishing` work for projects, areas and every later entity (pages, posts,
 * job posts…) without knowing their fields. One driver per entity, registered in `Publishing`.
 *
 * The "payload" is the full editor form of the entity in the shape of its Form Request, which is
 * also what a revision stores (DM-REV).
 *
 * @phpstan-type ChecklistItem array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}
 */
interface PublishingDriver
{
    /** The morph alias: `revisions.revisionable_type` and the segment of the preview URL. */
    public function type(): string;

    /** The activity-log name of the entity (ADM-LOG-001): `projects`, `locations`. */
    public function logName(): string;

    /** The word toasts use: "project", "area". */
    public function noun(): string;

    public function label(Model $entity): string;

    /** The public path of the saved entity, e.g. `/projects/lakeview-residence`. */
    public function publicPath(Model $entity): string;

    /**
     * The form payload of a saved or hydrated entity, in the canonical editor shape.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Model $entity): array;

    /**
     * An unsaved copy of the entity as the payload would make it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function hydrate(Model $entity, array $payload, bool $preview = false): Model;

    /**
     * Write the payload onto the live row. Never changes the publishing status.
     *
     * @param  array<string, mixed>  $payload
     */
    public function apply(?User $actor, Model $entity, array $payload, bool $log = true): Model;

    /**
     * The publish-only rules (VAL-05), read from a saved or hydrated entity; empty when there are none.
     *
     * @return list<ChecklistItem>
     */
    public function checklist(Model $entity): array;

    /**
     * Field names shown in a revision diff: payload key → label. A key not listed shows its own name.
     *
     * @return array<string, string>
     */
    public function fieldLabels(): array;

    /**
     * Payload keys that hold rich text, diffed as text with insertions and deletions marked.
     *
     * @return list<string>
     */
    public function htmlFields(): array;
}
