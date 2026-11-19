<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The row-menu verbs of the jobs list: Open, Close applications, Back to draft. Opening needs the same
 * checks as the editor (`JobPostChecklist`). Returns false when the job already has that status.
 */
final class SetJobPostStatus
{
    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly JobPostChecklist $checklist,
    ) {}

    /**
     * @throws ValidationException when the job cannot be opened yet
     */
    public function handle(User $actor, JobPost $job, JobStatus $to): bool
    {
        if ($job->status === $to) {
            return false;
        }

        if ($to === JobStatus::Open) {
            $problems = $this->checklist->problems($job);

            if ($problems !== []) {
                throw ValidationException::withMessages(['status' => $problems[0]]);
            }
        }

        $from = $job->status;

        $job->status = $to;

        if ($to === JobStatus::Open && $job->published_at === null) {
            $job->published_at = now();
        }

        $job->closed_at = $to === JobStatus::Closed ? now() : null;
        $job->save();

        $this->logActivity->handle(
            'jobs',
            'status_changed',
            "Job post {$to->label()}: {$job->title}",
            $actor,
            $job,
            ['label' => $job->title, 'from' => $from->value, 'to' => $to->value],
        );

        return true;
    }
}
