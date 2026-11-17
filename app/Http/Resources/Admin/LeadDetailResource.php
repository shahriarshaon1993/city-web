<?php

namespace App\Http\Resources\Admin;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Support\Leads\LeadPresenter;
use App\Support\Leads\LeadTypes;
use Illuminate\Http\Request;

/**
 * The lead drawer (CMP-ADM-LEAD, FR-LEAD-009): everything of the row plus the answers, campaign data,
 * files and the timeline. The IP address and user agent are never sent. Call / WhatsApp / email links
 * exist only for a user who may see the real contact details.
 *
 * @mixin Lead
 */
class LeadDetailResource extends LeadRowResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            ...parent::toArray($request),
            'country' => $this->country,
            'unit_type' => $this->unitType?->name,
            'message' => $this->message,
            'answers' => LeadPresenter::answers($this->resource),
            'form_location' => $this->form_location,
            'source_url' => $this->source_url,
            'referrer_host' => $this->referrer_host,
            'utm' => array_filter([
                'Source' => $this->utm_source,
                'Medium' => $this->utm_medium,
                'Campaign' => $this->utm_campaign,
                'Term' => $this->utm_term,
                'Content' => $this->utm_content,
            ]),
            'consent_text' => $this->consent_text,
            'lost_reason' => $this->lost_reason,
            'won_note' => $this->won_note,
            'attachments' => array_map(
                fn (array $file, int $index): array => [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'url' => route('admin.leads.attachments.show', ['lead' => $this->id, 'index' => $index]),
                ],
                $this->attachments ?? [],
                array_keys($this->attachments ?? []),
            ),
            'links' => LeadPresenter::links($this->resource, $actor),
            'timeline' => $this->activities
                ->reverse()
                ->map(fn (LeadActivity $activity): array => LeadPresenter::activity($activity))
                ->values()
                ->all(),
            'type_label' => LeadTypes::label($this->type),
        ];
    }
}
