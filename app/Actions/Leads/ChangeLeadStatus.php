<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Enums\LeadChange;
use App\Enums\LeadStatus;
use App\Events\LeadUpdated;
use App\Models\Lead;
use App\Models\User;
use App\Support\Leads\LeadReasons;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FR-LEAD-006: moves a lead along the pipeline and writes the change to its timeline with the user
 * and time. Won and Lost carry a reason (a select + an optional note, validated by the Form
 * Request); moving a lead out of Won / Lost clears it. Returns false when nothing changed.
 */
final class ChangeLeadStatus
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Lead $lead, LeadStatus $to, ?string $reason = null, ?string $note = null): bool
    {
        $from = $lead->status;

        if ($from === $to) {
            return false;
        }

        DB::transaction(function () use ($actor, $lead, $from, $to, $reason, $note): void {
            $list = $to === LeadStatus::Won ? LeadReasons::WON : LeadReasons::LOST;
            $label = $to->needsReason() ? ($list[$reason] ?? null) : null;
            $text = $label === null ? null : Str::limit($label.($note !== null && $note !== '' ? ' — '.$note : ''), 200, '');

            $lead->forceFill([
                'status' => $to,
                'lost_reason' => $to === LeadStatus::Lost ? $text : null,
                'won_note' => $to === LeadStatus::Won ? $text : null,
            ])->save();

            $lead->activities()->create([
                'user_id' => $actor->id,
                'kind' => LeadActivityKind::Status,
                'payload' => array_filter([
                    'from' => $from->value,
                    'to' => $to->value,
                    'reason' => $label,
                    'note' => $note !== '' ? $note : null,
                ]),
            ]);

            $this->logActivity->handle('leads', 'status_changed', "Lead moved from {$from->label()} to {$to->label()}", $actor, $lead, [
                'from' => $from->value,
                'to' => $to->value,
            ]);

            LeadUpdated::dispatch($lead, LeadChange::Status, $actor);
        });

        return true;
    }
}
