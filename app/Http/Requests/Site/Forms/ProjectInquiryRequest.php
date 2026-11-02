<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `project_inquiry`: step 1 (timeline, purpose, contact time — all optional,
 * the visitor may skip it) and step 2 (the contact details).
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
        return [
            ...$this->projectRules(required: true, unitType: true),
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
