<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;

/**
 * FR-CAR-005: moves an application along the pipeline (new → shortlisted → interview → rejected / hired).
 * Any status may move to any other. The activity entry names the application by its id and the role,
 * never the applicant (ADM-LOG: no personal data in the log of applications). Returns false when the
 * status is already the one asked for. A moved application counts as updated, which restarts its
 * 12-month retention (NFR-PRIV-04).
 */
final class ChangeApplicationStatus
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, JobApplication $application, ApplicationStatus $to): bool
    {
        $from = $application->status;

        if ($from === $to) {
            return false;
        }

        $application->forceFill(['status' => $to, 'reviewed_by' => $actor->id])->save();

        $this->logActivity->handle(
            'applications',
            'status_changed',
            "Application #{$application->id}: {$from->label()} → {$to->label()}",
            $actor,
            $application,
            ['from' => $from->value, 'to' => $to->value, 'job' => $application->job?->title],
        );

        return true;
    }
}
