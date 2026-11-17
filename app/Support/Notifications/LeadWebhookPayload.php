<?php

namespace App\Support\Notifications;

use App\Models\Lead;

/**
 * The JSON body of the lead webhook (FR-LEAD-012). What a CRM needs and nothing private: no IP,
 * user agent, analytics id or attachment paths.
 */
final class LeadWebhookPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Lead $lead, string $event, ?string $change, int $deliveryId): array
    {
        $lead->loadMissing(['project', 'assignee']);

        return array_filter([
            'delivery_id' => $deliveryId,
            'event' => $event,
            'change' => $change,
            'occurred_at' => now()->toIso8601String(),
            'lead' => [
                'reference' => $lead->reference,
                'type' => $lead->type,
                'status' => $lead->status->value,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'whatsapp' => $lead->whatsapp,
                'email' => $lead->email,
                'country' => $lead->country,
                'project' => $lead->project === null ? null : [
                    'id' => $lead->project->id,
                    'slug' => $lead->project->slug,
                    'name' => $lead->project->name,
                ],
                'message' => $lead->message,
                'answers' => $lead->answers,
                'attachments' => count($lead->attachments ?? []),
                'source_url' => $lead->source_url,
                'form_location' => $lead->form_location,
                'utm' => array_filter([
                    'source' => $lead->utm_source,
                    'medium' => $lead->utm_medium,
                    'campaign' => $lead->utm_campaign,
                    'term' => $lead->utm_term,
                    'content' => $lead->utm_content,
                ]),
                'referrer_host' => $lead->referrer_host,
                'assigned_to' => $lead->assignee === null ? null : [
                    'id' => $lead->assignee->id,
                    'name' => $lead->assignee->name,
                    'email' => $lead->assignee->email,
                ],
                'follow_up_at' => $lead->follow_up_at?->toIso8601String(),
                'lost_reason' => $lead->lost_reason,
                'won_note' => $lead->won_note,
                'touches_count' => $lead->touches_count,
                'consent_text' => $lead->consent_text,
                'created_at' => $lead->created_at?->toIso8601String(),
                'admin_url' => route('admin.leads.index', ['lead' => $lead->id]),
            ],
        ], fn (mixed $value): bool => $value !== null);
    }
}
