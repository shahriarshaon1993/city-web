<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TOAST-02: the reverse of ChangeLeadStatus. Puts the previous status back together with the Won /
 * Lost text it had, and records the undo on the timeline so the history stays honest.
 */
final class UndoLeadStatus
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Lead $lead, LeadStatus $previous, ?string $lostReason, ?string $wonNote): void
    {
        $from = $lead->status;

        DB::transaction(function () use ($actor, $lead, $from, $previous, $lostReason, $wonNote): void {
            $lead->forceFill([
                'status' => $previous,
                'lost_reason' => $previous === LeadStatus::Lost ? $lostReason : null,
                'won_note' => $previous === LeadStatus::Won ? $wonNote : null,
            ])->save();

            $lead->activities()->create([
                'user_id' => $actor->id,
                'kind' => LeadActivityKind::Status,
                'payload' => ['from' => $from->value, 'to' => $previous->value, 'undo' => true],
            ]);

            $this->logActivity->handle('leads', 'status_changed', "Lead status change undone: back to {$previous->label()}", $actor, $lead, [
                'from' => $from->value,
                'to' => $previous->value,
            ]);
        });
    }
}
