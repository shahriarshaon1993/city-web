<?php

namespace App\Actions\Privacy;

use App\Actions\Activity\LogActivity;
use App\Models\JobApplication;
use App\Models\Lead;
use App\Models\NewsletterSubscriber;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Queries\Admin\PersonalDataLookup;
use App\Support\Notifications\NotificationKind;
use App\Support\Privacy\PersonalDataTerm;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

/**
 * NFR-PRIV-03 / DEL-09: hard-deletes everything held for a phone and/or an email — leads (their
 * timeline goes with them, and their landowner documents), applications (and their CVs), newsletter
 * subscriptions — whether or not it sits in the Recycle bin, plus inbox notifications that name the
 * person. The only trace is one activity entry with counts and no personal data; the webhook log
 * keeps its rows with the lead link cleared (FR-LEAD-012).
 */
final class ErasePersonalData
{
    public function __construct(
        private readonly PersonalDataLookup $lookup,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @return array{leads: int, applications: int, subscriptions: int}
     */
    public function handle(User $actor, PersonalDataTerm $term): array
    {
        return DB::transaction(function () use ($actor, $term): array {
            $leads = $this->lookup->leads($term)->get();
            $applications = $this->lookup->applications($term)->get();
            $subscribers = $this->lookup->subscribers($term)->get();

            $this->deleteNotifications(
                array_values($leads->map(fn (Lead $lead): int => $lead->id)->all()),
                array_values($applications->map(fn (JobApplication $application): int => $application->id)->all()),
                [
                    ...$leads->map(fn (Lead $lead): string => $lead->name)->all(),
                    ...$applications->map(fn (JobApplication $application): string => $application->name)->values()->all(),
                ],
            );

            foreach ($leads as $lead) {
                $this->removeFromBin(Lead::binType(), $lead->id);
                $lead->binForceDeleting();
                $lead->forceDelete();
            }

            foreach ($applications as $application) {
                $this->removeFromBin(JobApplication::binType(), $application->id);
                $application->binForceDeleting();
                $application->forceDelete();
            }

            foreach ($subscribers as $subscriber) {
                $this->removeFromBin(NewsletterSubscriber::binType(), $subscriber->id);
                $subscriber->forceDelete();
            }

            $counts = ['leads' => $leads->count(), 'applications' => $applications->count(), 'subscriptions' => $subscribers->count()];

            $this->logActivity->handle('privacy', 'erased', 'Personal data erased on request', $actor, null, $counts);

            return $counts;
        });
    }

    private function removeFromBin(string $type, int $id): void
    {
        RecycleBinEntry::query()->where('type', $type)->where('trashable_id', $id)->delete();
    }

    /**
     * Inbox notices carry the person's name in their title. Those that name a lead or an application by
     * id go; the daily follow-up digest has no single id, so it goes when it names the person.
     *
     * @param  list<int>  $leadIds
     * @param  list<int>  $applicationIds
     * @param  list<string>  $names
     */
    private function deleteNotifications(array $leadIds, array $applicationIds, array $names): void
    {
        if ($leadIds === [] && $applicationIds === [] && $names === []) {
            return;
        }

        $types = [
            NotificationKind::NewLead->notificationClass(),
            NotificationKind::LeadAssigned->notificationClass(),
            NotificationKind::FollowUpsDue->notificationClass(),
            NotificationKind::NewApplication->notificationClass(),
        ];

        DatabaseNotification::query()
            ->whereIn('type', $types)
            ->where(function ($query) use ($leadIds, $applicationIds, $names): void {
                if ($leadIds !== []) {
                    $query->orWhereIn('data->lead_id', $leadIds);
                }

                if ($applicationIds !== []) {
                    $query->orWhereIn('data->application_id', $applicationIds);
                }

                foreach (array_unique($names) as $name) {
                    $escaped = addcslashes(trim((string) json_encode($name), '"'), '%_\\');
                    $query->orWhere('data', 'like', "%{$escaped}%");
                }
            })
            ->delete();
    }
}
