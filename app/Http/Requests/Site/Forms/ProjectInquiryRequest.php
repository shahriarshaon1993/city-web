<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Demo\DemoProjects;
use App\Support\Demo\DemoShortlist;
use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `project_inquiry`: step 1 (timeline, purpose, contact time — all optional,
 * the visitor may skip it) and step 2 (the contact details). From the
 * shortlist (FR-SHORT-004) it carries `projects` (several slugs) instead of
 * `project`; then no unit type is asked.
 */
class ProjectInquiryRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::ProjectInquiry;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // A `projects` key (even an empty list) switches to the shortlist form.
        $projectRules = array_map(
            fn (array $rules): array => ['exclude_with:projects', ...$rules],
            $this->projectRules(required: true, unitType: true),
        );

        return [
            ...$projectRules,
            'projects' => ['array', 'min:1', 'max:'.DemoShortlist::SHORTLIST_MAX],
            'projects.*' => ['string', 'distinct', Rule::in(array_column(DemoProjects::rows(), 'slug'))],
            'timeline' => ['nullable', Rule::in(FormOptions::values('timeline'))],
            'purpose' => ['nullable', Rule::in(FormOptions::values('purpose'))],
            'contact_time' => ['nullable', Rule::in(FormOptions::values('contact_time'))],
            ...$this->personRules(whatsapp: true),
            'message' => $this->messageRules(),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
