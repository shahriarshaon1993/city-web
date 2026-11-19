<?php

namespace App\Http\Resources\Admin;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the offices list. `job_posts` feeds the delete guard (DEL-03).
 *
 * @mixin Office
 */
class OfficeRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $block = $this->binUsageBlock($actor);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'phones' => $this->phones,
            'email' => $this->email,
            'is_head_office' => $this->is_head_office,
            'sort_order' => $this->sort_order,
            'delete_block' => $block,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
