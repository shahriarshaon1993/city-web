<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ADM-AUTH-006: the invite email with the 72-hour set-password link.
 * Sent synchronously, so it also works without a queue worker.
 */
class UserInvitation extends Notification
{
    public function __construct(
        public readonly string $url,
        public readonly string $roleName,
        public readonly int $validForHours,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You are invited to :app', ['app' => config('app.name')]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('You have been invited to the :app admin as :role.', [
                'app' => config('app.name'),
                'role' => $this->roleName,
            ]))
            ->action(__('Set your password'), $this->url)
            ->line(__('This link is valid for :hours hours and can be used once.', ['hours' => $this->validForHours]))
            ->line(__('If you were not expecting this invitation, you can ignore this email.'));
    }
}
