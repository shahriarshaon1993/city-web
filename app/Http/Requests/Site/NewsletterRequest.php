<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Newsletter sign-up (footer, PG-14): email and an explicit consent
 * (spec/leads.md). VAL-01 defaults: `max` = column length (DM-SUBS).
 */
class NewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'consent' => ['accepted'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'form_location' => ['nullable', 'string', 'max:40'],
        ];
    }
}
