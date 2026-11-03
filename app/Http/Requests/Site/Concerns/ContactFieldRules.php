<?php

namespace App\Http\Requests\Site\Concerns;

use App\Rules\PhoneNumber;
use App\Support\Forms\Countries;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Person, consent and tracking fields shared by the lead forms (7.1) and the
 * job application (8.2). Defaults follow crud.md VAL-01: strings are trimmed
 * by the framework, `max` = column length, optional = `nullable`.
 */
trait ContactFieldRules
{
    protected function prepareForValidation(): void
    {
        $country = $this->input('phone_country');

        $this->merge([
            'phone_country' => is_string($country) && $country !== '' ? strtoupper($country) : Countries::DEFAULT,
        ]);
    }

    /**
     * Name, phone (+ country), optional "WhatsApp = same as phone" and email.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function personRules(bool $email = false, bool $whatsapp = false): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone_country' => ['required', Rule::in(Countries::codes())],
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
            'email' => [$email ? 'required' : 'nullable', 'email:rfc', 'max:190'],
            ...($whatsapp ? ['whatsapp_same' => ['nullable', 'boolean']] : []),
        ];
    }

    /**
     * FR-LEAD-014: the exact text shown is stored with the record (15.1, 17.1).
     *
     * @return array<string, array<int, string>>
     */
    protected function consentRules(): array
    {
        return ['consent' => ['accepted']];
    }

    /**
     * Source page, form location and the attribution captured in
     * sessionStorage (FR-LEAD-001); all optional hidden fields.
     *
     * @return array<string, array<int, string>>
     */
    protected function trackingRules(): array
    {
        $rules = [
            'source_url' => ['nullable', 'string', 'max:500'],
            'form_location' => ['nullable', 'string', 'max:40'],
            'referrer_host' => ['nullable', 'string', 'max:190'],
        ];

        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $rules["utm_{$key}"] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }
}
