<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * FR-LEAD-009: the date a salesperson wants to call back. `null` clears it ("Done"). Overdue
 * highlighting and the "My follow-ups" list read `follow_up_at`. Returns false when it did not change.
 */
final class SetLeadFollowUp
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Lead $lead, ?CarbonInterface $at): bool
    {
        $previous = $lead->follow_up_at;

        $unchanged = $previous === null || $at === null ? $previous === $at : $previous->equalTo($at);

        if ($unchanged) {
            return false;
        }

        DB::transaction(function () use ($actor, $lead, $at, $previous): void {
            $lead->forceFill(['follow_up_at' => $at])->save();

            $lead->activities()->create([
                'user_id' => $actor->id,
                'kind' => LeadActivityKind::FollowUp,
                'payload' => array_filter([
                    'at' => $at?->toIso8601String(),
                    'previous' => $previous?->toIso8601String(),
                ]),
            ]);

            $this->logActivity->handle('leads', 'follow_up', $at === null ? 'Lead follow-up cleared' : 'Lead follow-up set', $actor, $lead);
        });

        return true;
    }
}
