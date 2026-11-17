<?php

namespace App\Actions\Notifications;

use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Mail;

/**
 * §10.8 "lead assigned to you" after a manager hands a lead over. Nothing is sent when someone
 * assigns a lead to themselves or removes the owner; the email copy follows FR-LEAD-008b.
 */
final class NotifyLeadAssigned
{
    public function handle(Lead $lead, ?User $actor): void
    {
        $lead->loadMissing(['project', 'assignee']);
        $assignee = $lead->assignee;

        if ($assignee === null || ! $assignee->is_active || $assignee->id === $actor?->id) {
            return;
        }

        $assignee->notify(new LeadAssignedNotification($lead, $actor?->name));

        if ((bool) Settings::get('notifications', 'email_enabled', false)) {
            Mail::to($assignee->email)->send(new NewLeadMail($lead, assigned: true));
        }
    }
}
