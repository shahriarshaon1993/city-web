<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `callback`: "Call me back" — name, phone and a preferred time.
 */
class CallbackRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::Callback;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->projectRules(required: false),
            ...$this->personRules(),
            'contact_time' => ['required', Rule::in(FormOptions::values('contact_time'))],
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
