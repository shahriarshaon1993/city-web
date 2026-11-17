<?php

namespace App\Http\Resources\Admin;

use App\Models\Lead;
use App\Support\Leads\LeadPresenter;
use App\Support\Leads\LeadTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the leads list (ADM-SCR-11). Phone and email are masked server-side for a user without
 * `leads.view-contact`; `can` says what the user may do (ARC-005), the Policy re-checks.
 *
 * @mixin Lead
 */
class LeadRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => LeadTypes::label($this->type),
            ...LeadPresenter::contact($this->resource, $actor),
            'project' => $this->project === null ? null : ['id' => $this->project->id, 'name' => $this->project->name],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assignee' => $this->assignee === null ? null : ['id' => $this->assignee->id, 'name' => $this->assignee->name],
            'follow_up_at' => $this->follow_up_at?->toIso8601String(),
            'follow_up_overdue' => $this->followUpIsOverdue(),
            'created_at' => $this->created_at?->toIso8601String(),
            'last_touched_at' => $this->last_touched_at?->toIso8601String(),
            'touches_count' => $this->touches_count,
            'source' => LeadTypes::source($this->resource),
            'can' => [
                'update' => $actor->can('update', $this->resource),
                'assign' => $actor->can('assign', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
