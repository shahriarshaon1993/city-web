<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormType;

/**
 * `brochure_download`: name, phone, optional email; stays on the page and
 * starts the download (FR-LEAD-005).
 */
class BrochureRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::BrochureDownload;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->projectRules(required: true),
            ...$this->personRules(),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
