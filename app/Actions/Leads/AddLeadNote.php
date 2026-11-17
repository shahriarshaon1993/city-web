<?php

namespace App\Actions\Leads;

use App\Actions\Activity\LogActivity;
use App\Enums\LeadActivityKind;
use App\Enums\LeadChange;
use App\Events\LeadUpdated;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Support\Html\BasicHtml;
use Illuminate\Validation\ValidationException;

/**
 * FR-LEAD-009: an internal note on a lead's timeline. Notes are rich text limited to what BasicHtml
 * keeps (NFR-SEC-03); a note with no visible text is refused. They are never shown to the visitor.
 */
final class AddLeadNote
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException when the note has no text
     */
    public function handle(User $actor, Lead $lead, string $html): LeadActivity
    {
        $clean = BasicHtml::clean($html);

        if ($clean === null) {
            throw ValidationException::withMessages(['note' => 'Write something before you add the note.']);
        }

        $activity = $lead->activities()->create([
            'user_id' => $actor->id,
            'kind' => LeadActivityKind::Note,
            'payload' => ['html' => $clean],
        ]);

        $this->logActivity->handle('leads', 'noted', 'Note added to a lead', $actor, $lead);

        LeadUpdated::dispatch($lead, LeadChange::Note, $actor);

        return $activity;
    }
}
