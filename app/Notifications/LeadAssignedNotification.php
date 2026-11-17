<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Support\Leads\LeadTypes;
use Illuminate\Notifications\Notification;

/** §10.8 "lead assigned to you": automatic assignment, a manager's reassignment or a manual lead. */
class LeadAssignedNotification extends Notification
{
    public function __construct(public readonly Lead $lead, public readonly ?string $byName = null) {}

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
            'kind' => 'lead_assigned',
            'title' => "Lead assigned to you: {$this->lead->name}",
            'body' => implode(' · ', array_filter([
                LeadTypes::label($this->lead->type),
                $project,
                $this->byName === null ? null : "by {$this->byName}",
            ])),
            'url' => route('admin.leads.index', ['lead' => $this->lead->id], false),
            'lead_id' => $this->lead->id,
        ];
    }
}
