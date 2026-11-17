<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Support\Auth\SystemRole;

/**
 * FR-LEAD-007: a new lead goes to its project's default assignee; otherwise to the active Sales
 * Executive with the fewest open leads (round-robin by load); otherwise it stays unassigned. The
 * choice is written to the lead's timeline (DM-LEADH `assignment`).
 */
final class AssignLead
{
    public function handle(Lead $lead, ?Project $project): ?User
    {
        $assignee = $this->projectDefault($project);
        $rule = 'project_default';

        if ($assignee === null) {
            $assignee = $this->leastLoadedExecutive();
            $rule = 'round_robin';
        }

        if ($assignee === null) {
            return null;
        }

        $lead->forceFill(['assigned_to' => $assignee->id])->save();

        $lead->activities()->create([
            'kind' => LeadActivityKind::Assignment,
            'payload' => ['to' => $assignee->id, 'name' => $assignee->name, 'rule' => $rule],
        ]);

        return $assignee;
    }

    private function projectDefault(?Project $project): ?User
    {
        if ($project?->default_assignee_id === null) {
            return null;
        }

        return User::query()->where('is_active', true)->find($project->default_assignee_id);
    }

    private function leastLoadedExecutive(): ?User
    {
        return User::query()
            ->role(SystemRole::SalesExecutive->value)
            ->where('is_active', true)
            ->withCount(['assignedLeads as open_leads_count' => fn ($query) => $query->whereNotIn('status', LeadStatus::closedValues())])
            ->orderBy('open_leads_count')
            ->orderBy('id')
            ->first();
    }
}
