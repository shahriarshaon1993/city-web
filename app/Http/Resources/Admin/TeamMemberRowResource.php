<?php

namespace App\Http\Resources\Admin;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the team list.
 *
 * @mixin TeamMember
 */
class TeamMemberRowResource extends JsonResource
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
            'role_title' => $this->role_title,
            'group' => ['value' => $this->group->value, 'label' => $this->group->label()],
            'thumb' => $this->photo?->thumbUrl(),
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
