<?php

namespace App\Http\Resources\Admin;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the partners list; the dialog edits from the same values.
 *
 * @mixin Partner
 */
class PartnerRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_asset_id' => $this->logo_asset_id,
            'logo' => $this->logo === null ? null : (new MediaAssetResource($this->logo))->resolve($request),
            'url' => $this->url,
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
