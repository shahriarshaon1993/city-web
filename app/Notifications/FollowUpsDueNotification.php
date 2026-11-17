<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * §10.8 "follow-up due today": one digest per person each morning at 09:00 Dhaka time. A single
 * lead links straight to it; several link to the list of that person's open leads.
 */
class FollowUpsDueNotification extends Notification
{
    /**
     * @param  list<string>  $names  The leads' names, in due order.
     */
    public function __construct(public readonly array $names, public readonly ?int $onlyLeadId) {}

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
        $count = count($this->names);
        $shown = array_slice($this->names, 0, 3);

        return [
            'kind' => 'follow_up_due',
            'title' => $count === 1 ? "Follow-up due today: {$this->names[0]}" : "{$count} follow-ups due today",
            'body' => $count === 1 ? null : implode(', ', $shown).($count > 3 ? ' and '.($count - 3).' more' : ''),
            'url' => $this->onlyLeadId === null
                ? route('admin.leads.index', ['filter' => ['follow_up' => 'today', 'assignee' => 'me']], false)
                : route('admin.leads.index', ['lead' => $this->onlyLeadId], false),
            'lead_id' => $this->onlyLeadId,
        ];
    }
}
