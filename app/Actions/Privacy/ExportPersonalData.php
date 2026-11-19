<?php

namespace App\Actions\Privacy;

use App\Actions\Activity\LogActivity;
use App\Models\JobApplication;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Queries\Admin\PersonalDataLookup;
use App\Support\Leads\LeadTypes;
use App\Support\Privacy\PersonalDataTerm;

/**
 * NFR-PRIV-03: one JSON document with everything held for a phone and/or an email — the person's own
 * words and details, the staff timeline on their leads and applications, and the consent text they
 * saw. CV files are not inlined; the document names them. Logged with counts only, never the person.
 */
final class ExportPersonalData
{
    public function __construct(
        private readonly PersonalDataLookup $lookup,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $actor, PersonalDataTerm $term): array
    {
        $leads = $this->lookup->leads($term)->with(['project:id,name', 'activities'])->orderBy('id')->get();
        $applications = $this->lookup->applications($term)->with('job:id,title')->orderBy('id')->get();
        $subscriptions = $this->lookup->subscribers($term)->orderBy('id')->get();

        $this->logActivity->handle('privacy', 'exported', 'Personal data exported', $actor, null, [
            'leads' => $leads->count(),
            'applications' => $applications->count(),
            'subscriptions' => $subscriptions->count(),
        ]);

        return [
            'exported_at' => now('Asia/Dhaka')->toIso8601String(),
            'searched_for' => $term->toArray(),
            'leads' => $leads->map(fn (Lead $lead): array => [
                'reference' => $lead->reference,
                'received_at' => $lead->created_at?->toIso8601String(),
                'type' => LeadTypes::label($lead->type),
                'status' => $lead->status->label(),
                'name' => $lead->name,
                'phone' => $lead->phone,
                'whatsapp' => $lead->whatsapp,
                'email' => $lead->email,
                'country' => $lead->country,
                'project' => $lead->project?->name,
                'message' => $lead->message,
                'answers' => $lead->answers,
                'attachments' => array_map(fn (array $file): string => $file['name'], $lead->attachments ?? []),
                'page' => $lead->source_url,
                'campaign' => array_filter([
                    'source' => $lead->utm_source,
                    'medium' => $lead->utm_medium,
                    'campaign' => $lead->utm_campaign,
                    'term' => $lead->utm_term,
                    'content' => $lead->utm_content,
                ]),
                'referrer' => $lead->referrer_host,
                'ip_address' => $lead->ip,
                'consent_text' => $lead->consent_text,
                'in_recycle_bin' => $lead->trashed(),
                'timeline' => $lead->activities->map(fn (LeadActivity $activity): array => [
                    'at' => $activity->created_at?->toIso8601String(),
                    'kind' => $activity->kind->value,
                    'details' => $activity->payload,
                ])->all(),
            ])->all(),
            'applications' => $applications->map(fn (JobApplication $application): array => [
                'received_at' => $application->created_at?->toIso8601String(),
                'role' => $application->job->title ?? 'General CV',
                'status' => $application->status->label(),
                'name' => $application->name,
                'phone' => $application->phone,
                'email' => $application->email,
                'current_position' => $application->current_position,
                'years_experience' => $application->years_experience,
                'expected_salary' => $application->expected_salary,
                'linkedin' => $application->linkedin_url,
                'cover_letter' => $application->cover_letter,
                'cv_file' => $application->cv_name,
                'staff_notes' => $application->notes,
                'consent_text' => $application->consent_text,
                'in_recycle_bin' => $application->trashed(),
            ])->all(),
            'newsletter_subscriptions' => $subscriptions->map(fn (NewsletterSubscriber $subscriber): array => [
                'email' => $subscriber->email,
                'status' => $subscriber->status->value,
                'subscribed_at' => $subscriber->created_at?->toIso8601String(),
                'confirmed_at' => $subscriber->confirmed_at?->toIso8601String(),
                'signed_up_on' => $subscriber->source_url,
                'consent_text' => $subscriber->consent_text,
                'in_recycle_bin' => $subscriber->trashed(),
            ])->all(),
        ];
    }
}
