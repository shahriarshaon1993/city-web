<?php

namespace App\Http\Resources\Admin;

use App\Models\RecycleBinEntry;
use App\Support\RecycleBin\RestoreConflict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the Recycle bin table (DEL-04). `conflicts` lists what the user must decide before
 * a restore (DEL-05); the bin opens its dialog from them. `purge_at` null = never auto-deleted.
 *
 * @mixin RecycleBinEntry
 */
class RecycleBinRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $item = $this->item();

        return [
            'id' => $this->id,
            'label' => $this->label,
            'thumbnail' => $this->thumbnail,
            'type' => $this->type,
            'type_label' => $this->typeLabel(),
            'deleted_by' => $this->deletedBy?->name,
            'deleted_at' => $this->deleted_at->toIso8601String(),
            'purge_at' => $this->purge_at?->toIso8601String(),
            'url' => $item?->binUrl(),
            'conflicts' => $item === null ? [] : array_map(
                fn (RestoreConflict $conflict): array => $conflict->toArray(),
                $item->binRestoreConflicts(),
            ),
            'can' => [
                'restore' => $actor->can('restore', $this->resource),
                'forever' => $actor->can('forceDelete', $this->resource),
            ],
        ];
    }
}
