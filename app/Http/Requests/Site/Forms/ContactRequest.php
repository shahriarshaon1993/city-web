<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `contact` (PG-17): subject and a 20–2000 character message.
 */
class ContactRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::Contact;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->projectRules(required: false),
            ...$this->personRules(),
            'subject' => ['required', Rule::in(FormOptions::values('subject'))],
            'message' => $this->messageRules(FormOptions::CONTACT_MESSAGE_MIN, required: true),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
