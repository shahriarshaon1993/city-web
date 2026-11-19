<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Support\Careers\JobDates;

/**
 * FR-CAR-003 / ARC-012: closes every Open job whose deadline day has passed. Scheduled at 00:05 Asia/Dhaka
 * by `careers:close-expired`, so a deadline of 18 Nov closes at 00:05 on 19 Nov. Closed jobs keep their
 * page for 30 days and then answer 410.
 */
final class CloseExpiredJobPosts
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @return int number of jobs closed
     */
    public function handle(): int
    {
        $closed = 0;

        JobPost::query()
            ->where('status', JobStatus::Open->value)
            ->where('deadline', '<', JobDates::today()->toDateString())
            ->orderBy('id')
            ->each(function (JobPost $job) use (&$closed): void {
                $job->forceFill(['status' => JobStatus::Closed, 'closed_at' => now()])->save();
                $this->logActivity->handle('jobs', 'closed', "Job post closed after its deadline: {$job->title}", null, $job, ['label' => $job->title]);
                $closed++;
            });

        return $closed;
    }
}
