<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Models\JobApplication;
use App\Models\RecycleBinEntry;

/**
 * NFR-PRIV-04 / DEL-06: an application nobody has touched for 12 months is deleted for good with its
 * CV, whether or not it was in the Recycle bin. The activity entry carries the count only. Scheduled
 * nightly by `applications:purge`.
 */
final class PurgeOldApplications
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @return int number of applications removed
     */
    public function handle(): int
    {
        $removed = 0;

        JobApplication::withTrashed()
            ->where('updated_at', '<', now()->subDays(JobApplication::RETENTION_DAYS))
            ->orderBy('id')
            ->each(function (JobApplication $application) use (&$removed): void {
                $application->binForceDeleting();
                RecycleBinEntry::query()->where('type', JobApplication::binType())->where('trashable_id', $application->id)->delete();
                $application->forceDelete();
                $removed++;
            });

        if ($removed > 0) {
            $this->logActivity->handle('applications', 'purged', "{$removed} application(s) removed after 12 months, with their CVs", null, null, ['count' => $removed]);
        }

        return $removed;
    }
}
