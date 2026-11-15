<?php

namespace App\Support\RecycleBin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An entity that Delete moves into the Recycle bin (crud.md DEL-01..08). Every method has a
 * default in `BinBehaviour`; an entity overrides what differs. Implementers are Eloquent models
 * that use SoftDeletes and are listed in `BinTypes`.
 */
interface Trashable
{
    /** Morph-map alias, stored as the bin row's `type` (e.g. `user`). */
    public static function binType(): string;

    /** Singular, capitalised: "User". Used in toasts and the type column. */
    public static function binTypeLabel(): string;

    /** Permission that allows deleting and restoring this type (spec/admin.md §10.2). */
    public static function binPermission(): string;

    /**
     * The soft-deleted rows of this type (Eloquent SoftDeletes::onlyTrashed).
     *
     * @return Builder<covariant Model>
     */
    public static function binTrashedQuery(): Builder;

    /** Days until auto-delete (DEL-06); null = never. */
    public static function binRetentionDays(): ?int;

    /** What the bin shows as the item's name. */
    public function binLabel(): string;

    /** Thumbnail URL for the bin row, when the entity has an image. */
    public function binThumbnail(): ?string;

    /** Admin URL that opens the item after a restore ("Open" in the toast). */
    public function binUrl(): ?string;

    /**
     * DEL-03: the reason this item cannot be deleted right now, or null. Written as the sentence
     * the disabled button shows (HELP-08).
     */
    public function binUsageBlock(User $actor): ?string;

    /**
     * DEL-05: what stops a restore until the user decides (slug taken, parent in the bin).
     *
     * @return list<RestoreConflict>
     */
    public function binRestoreConflicts(): array;

    /**
     * Applies the user's answers to the conflicts, before the item is restored.
     *
     * @param  array<string, mixed>  $resolution  conflict `field` → the new value, or `true` for a parent
     */
    public function binResolveConflicts(array $resolution): void;

    /**
     * The Eloquent soft-delete operations the bin relies on (provided by Model and SoftDeletes).
     *
     * @return bool|null
     */
    public function delete();

    /** @return bool|null */
    public function restore();

    /** @return bool|null */
    public function forceDelete();

    /** Runs after the item was soft-deleted (end sessions, detach…). */
    public function binTrashed(): void;

    /** DEL-07: delete private files (CVs, documents) before the row is removed for good. */
    public function binForceDeleting(): void;
}
