<?php

namespace App\Actions\Notifications;

use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\NewApplicationNotification;

/**
 * Tells everyone who manages applications (HR, Admin) that one arrived. An application flagged for
 * review is announced like any other: the flag shows in the list. Called by `SubmitJobApplication`.
 */
final class NotifyNewApplication
{
    public function handle(JobApplication $application): void
    {
        $managers = User::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user): bool => $user->can('applications.manage'));

        foreach ($managers as $manager) {
            $manager->notify(new NewApplicationNotification($application));
        }
    }
}
