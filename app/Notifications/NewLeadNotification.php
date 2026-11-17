<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Support\Leads\LeadTypes;
use Illuminate\Notifications\Notification;

/**
 * §10.8 "new lead": for the people who manage leads (not the assignee, who gets LeadAssigned).
 * Database only; the optional email copy is `NewLeadMail` (FR-LEAD-008b). No phone or email is
 * stored in the notification, so it cannot leak contact details to a role that masks them.
 */
class NewLeadNotification extends Notification
{
    public function __construct(public readonly Lead $lead) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $project = $this->lead->project?->name;

        return [
            'kind' => 'new_lead',
            'title' => "New lead: {$this->lead->name}",
            'body' => LeadTypes::label($this->lead->type).($project === null ? '' : " · {$project}"),
            'url' => route('admin.leads.index', ['lead' => $this->lead->id], false),
            'lead_id' => $this->lead->id,
        ];
    }
}
