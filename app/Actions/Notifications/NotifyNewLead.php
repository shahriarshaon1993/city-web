<?php

namespace App\Actions\Notifications;

use App\Enums\LeadStatus;
use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\NewLeadNotification;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Mail;

/**
 * FR-LEAD-008: tells the team about a new lead. The assignee gets "assigned to you"; everyone who
 * can assign leads (managers, Admin) gets "new lead"; nobody is told twice or about their own action.
 * When email is switched on (default off) the assignee and the configured addresses for this lead
 * type also get an email. Spam is never announced.
 */
final class NotifyNewLead
{
    public function handle(Lead $lead, ?User $actor = null): void
    {
        if ($lead->status === LeadStatus::Spam) {
            return;
        }

        $lead->loadMissing(['project', 'assignee']);

        $assignee = $lead->assignee?->is_active ? $lead->assignee : null;
        $told = array_filter([$actor?->id, $assignee?->id]);

        if ($assignee !== null && $assignee->id !== $actor?->id) {
            $assignee->notify(new LeadAssignedNotification($lead, $actor?->name));
        }

        $managers = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $told)
            ->get()
            ->filter(fn (User $user): bool => $user->can('leads.assign'));

        foreach ($managers as $manager) {
            $manager->notify(new NewLeadNotification($lead));
        }

        $this->email($lead, $assignee);
    }

    private function email(Lead $lead, ?User $assignee): void
    {
        if (! (bool) Settings::get('notifications', 'email_enabled', false)) {
            return;
        }

        $configured = Settings::get('notifications', $lead->type, []);
        $addresses = [];

        foreach ([$assignee?->email, ...(is_array($configured) ? $configured : [])] as $address) {
            if (is_string($address) && filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                $addresses[strtolower($address)] ??= $address;
            }
        }

        foreach ($addresses as $address) {
            Mail::to($address)->send(new NewLeadMail($lead));
        }
    }
}
