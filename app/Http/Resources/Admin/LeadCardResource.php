<?php

namespace App\Http\Resources\Admin;

use App\Models\Lead;
use App\Support\Leads\LeadPresenter;
use App\Support\Leads\LeadTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One card of the lead board (CMP-ADM-KANBAN): name, project, type, phone (masked for the Viewer),
 * age, owner and follow-up. `can.update` tells the board whether the card may be dragged.
 *
 * @mixin Lead
 */
class LeadCardResource extends JsonResource
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
            'type_label' => LeadTypes::label($this->type),
            'phone' => LeadPresenter::contact($this->resource, $actor)['phone'],
            'project' => $this->project?->name,
            'status' => $this->status->value,
            'assignee' => $this->assignee === null ? null : ['id' => $this->assignee->id, 'name' => $this->assignee->name],
            'follow_up_at' => $this->follow_up_at?->toIso8601String(),
            'follow_up_overdue' => $this->followUpIsOverdue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => ['update' => $actor->can('update', $this->resource)],
        ];
    }
}
