<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Notifications\Notification;

/**
 * §10.8 "new application": for the people who manage applications. Database only. The notification
 * names the applicant and the role (they can already read both in the applications list) but holds
 * no phone number or email.
 */
class NewApplicationNotification extends Notification
{
    public function __construct(public readonly JobApplication $application) {}

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
        $this->application->loadMissing('job');

        return [
            'kind' => 'new_application',
            'title' => "New application: {$this->application->name}",
            'body' => $this->application->job->title ?? 'General CV',
            'url' => route('admin.applications.index', ['application' => $this->application->id], false),
            'application_id' => $this->application->id,
        ];
    }
}
