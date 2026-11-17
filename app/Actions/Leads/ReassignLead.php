<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-LEAD-007: a Sales Manager (or Admin) hands a lead to someone else, or takes the owner away
 * (`null`). The change goes on the timeline; the automatic choice at creation stays in AssignLead.
 * Returns false when the lead already had this owner.
 */
final class ReassignLead
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Lead $lead, ?User $assignee): bool
    {
        if ($lead->assigned_to === $assignee?->id) {
            return false;
        }

        DB::transaction(function () use ($actor, $lead, $assignee): void {
            $previous = $lead->assignee?->name;

            $lead->forceFill(['assigned_to' => $assignee?->id])->save();
            $lead->setRelation('assignee', $assignee);

            $lead->activities()->create([
                'user_id' => $actor->id,
                'kind' => LeadActivityKind::Assignment,
                'payload' => array_filter([
                    'to' => $assignee?->id,
                    'name' => $assignee?->name,
                    'from' => $previous,
                    'rule' => 'manual',
                ]),
            ]);

            $this->logActivity->handle(
                'leads',
                'assigned',
                $assignee === null ? 'Lead left unassigned' : "Lead assigned to {$assignee->name}",
                $actor,
                $lead,
                ['to' => $assignee?->id],
            );
        });

        return true;
    }
}
