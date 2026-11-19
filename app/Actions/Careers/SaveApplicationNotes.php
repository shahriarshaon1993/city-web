<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Models\JobApplication;
use App\Models\User;

/**
 * FR-CAR-005: the HR team's internal notes on an application (one text, never shown to the applicant).
 * The log records that notes changed, not what they say. Returns false when nothing changed.
 */
final class SaveApplicationNotes
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, JobApplication $application, ?string $notes): bool
    {
        $notes = $notes === null || trim($notes) === '' ? null : trim($notes);

        if ($notes === $application->notes) {
            return false;
        }

        $application->forceFill(['notes' => $notes, 'reviewed_by' => $actor->id])->save();

        $this->logActivity->handle('applications', 'updated', "Application #{$application->id}: notes updated", $actor, $application);

        return true;
    }
}
