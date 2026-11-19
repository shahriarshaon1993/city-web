<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityKind;
use App\Enums\LeadStatus;
use App\Mail\LeadAutoresponderMail;
use App\Models\Lead;
use App\Support\Notifications\TemplateTokens;
use App\Support\Settings\Settings;
use Illuminate\Support\Facades\Mail;

/**
 * FR-LEAD-008c: the optional automatic reply to a visitor who left an email address (default off).
 * Only a new lead from a public form gets one — never a repeat enquiry, spam, a lead waiting for
 * review (could be a bot) or one that staff added by hand. The send goes on the lead's timeline.
 */
final class SendLeadAutoresponder
{
    public function handle(Lead $lead): bool
    {
        if (! (bool) Settings::get('notifications', 'autoresponder_enabled', false)
            || $lead->email === null
            || in_array($lead->type, [Lead::MANUAL_TYPE, Lead::IMPORT_TYPE], true)
            || $lead->status !== LeadStatus::New) {
            return false;
        }

        $lead->loadMissing('project');

        $tokens = ['name' => $lead->name, 'project' => $lead->project->name ?? config('app.name')];
        $subject = TemplateTokens::fill((string) Settings::get('notifications', 'autoresponder_subject', ''), $tokens);
        $body = TemplateTokens::fill((string) Settings::get('notifications', 'autoresponder_body', ''), $tokens);

        if ($subject === '' || trim($body) === '') {
            return false;
        }

        Mail::to($lead->email)->send(new LeadAutoresponderMail($subject, $body));

        $lead->activities()->create([
            'kind' => LeadActivityKind::Email,
            'payload' => ['to' => 'visitor', 'subject' => $subject],
        ]);

        return true;
    }
}
