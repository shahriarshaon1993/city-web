<?php

namespace App\Actions\Notifications;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\FollowUpsDueNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * §10.8 "follow-up due today" (daily 09:00, Asia/Dhaka): one digest per person for the open leads
 * of theirs whose follow-up falls today. Running it twice on the same day does not repeat it.
 * Returns how many people were notified.
 */
final class NotifyFollowUpsDue
{
    public function handle(?CarbonInterface $now = null): int
    {
        $today = CarbonImmutable::instance($now ?? now())->timezone('Asia/Dhaka');

        $leads = Lead::query()
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', LeadStatus::closedValues())
            ->whereBetween('follow_up_at', [$today->startOfDay()->utc(), $today->endOfDay()->utc()])
            ->orderBy('follow_up_at')
            ->get(['id', 'name', 'assigned_to'])
            ->groupBy('assigned_to');

        $notified = 0;

        foreach ($leads as $userId => $own) {
            $user = User::query()->where('is_active', true)->find($userId);

            if ($user === null || $this->alreadyToldToday($user, $today)) {
                continue;
            }

            $user->notify(new FollowUpsDueNotification(
                $own->map(fn (Lead $lead): string => $lead->name)->values()->all(),
                $own->count() === 1 ? $own->first()->id : null,
            ));

            $notified++;
        }

        return $notified;
    }

    private function alreadyToldToday(User $user, CarbonImmutable $today): bool
    {
        return $user->notifications()
            ->where('type', FollowUpsDueNotification::class)
            ->where('created_at', '>=', $today->startOfDay()->utc())
            ->exists();
    }
}
