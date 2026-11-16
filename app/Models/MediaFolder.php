<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DM-MFOLD: a library folder, nested up to 3 levels (CMS-MED-002). Deleted for good, only when empty.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int $sort_order
 */
#[Fillable(['parent_id', 'name', 'sort_order'])]
class MediaFolder extends Model
{
    /** CMS-MED-002: a root folder is level 1. */
    public const int MAX_DEPTH = 3;

    public const int NAME_MAX = 60;

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaAsset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'folder_id');
    }

    /**
     * Ids from the root down to this folder (the folder itself last). Three levels at most, so a
     * walk up the parents is cheap.
     *
     * @return list<int>
     */
    public function lineage(): array
    {
        $ids = [$this->id];
        $parentId = $this->parent_id;

        while ($parentId !== null && count($ids) <= self::MAX_DEPTH + 1) {
            array_unshift($ids, $parentId);
            $parentId = self::query()->whereKey($parentId)->value('parent_id');
        }

        return $ids;
    }

    /** 1 for a root folder. */
    public function depth(): int
    {
        return count($this->lineage());
    }

    /** Levels this folder spans with its subfolders: 1 without children. */
    public function height(): int
    {
        $levels = 1;
        $ids = [$this->id];

        while ($ids !== [] && $levels <= self::MAX_DEPTH + 1) {
            $ids = self::query()->whereIn('parent_id', $ids)->pluck('id')->all();

            if ($ids !== []) {
                $levels++;
            }
        }

        return $levels;
    }

    /**
     * This folder and every folder below it.
     *
     * @return list<int>
     */
    public function subtreeIds(): array
    {
        $all = [$this->id];
        $ids = [$this->id];

        while ($ids !== []) {
            $ids = self::query()->whereIn('parent_id', $ids)->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();
            $all = [...$all, ...$ids];
        }

        return $all;
    }
}
