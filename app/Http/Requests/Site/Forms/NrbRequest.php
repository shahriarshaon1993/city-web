<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\Countries;
use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `nrb` (PG-11): country of residence, phone with country code and the
 * contact time in Bangladesh time.
 */
class NrbRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::Nrb;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->personRules(email: true, whatsapp: true),
            'country' => ['required', Rule::in(Countries::codes())],
            'contact_time' => ['required', Rule::in(FormOptions::values('contact_time'))],
            'budget_band' => ['nullable', Rule::in(FormOptions::values('budget'))],
            'message' => $this->messageRules(),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
