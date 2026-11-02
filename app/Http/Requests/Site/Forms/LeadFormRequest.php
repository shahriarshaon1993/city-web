<?php

namespace App\Http\Requests\Site\Forms;

use App\Rules\PhoneNumber;
use App\Support\Demo\DemoProjectDetail;
use App\Support\Demo\DemoProjects;
use App\Support\Forms\Countries;
use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared parts of the seven lead forms (spec/leads.md "Lead types and
 * forms"). One concrete Form Request per type (ARC-004); the defaults follow
 * crud.md VAL-01 where the spec is silent: strings are trimmed by the
 * framework, `max` = column length (DM-LEAD), optional = `nullable`.
 */
abstract class LeadFormRequest extends FormRequest
{
    abstract public function type(): FormType;

    public function authorize(): bool
    {
        return true;
    }

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
     * FR-LEAD-014: the exact text shown is stored with the lead (15.1).
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

    /**
     * The project the form was opened for (slug) and, optionally, one of its unit types.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function projectRules(bool $required, bool $unitType = false): array
    {
        $slugs = array_column(DemoProjects::rows(), 'slug');

        return [
            'project' => [$required ? 'required' : 'nullable', 'string', Rule::in($slugs)],
            ...($unitType ? ['unit_type' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $units = DemoProjectDetail::find((string) $this->input('project'))['unit_types'] ?? [];

                    if (! in_array((int) $value, array_column($units, 'id'), true)) {
                        $fail('validation.in')->translate();
                    }
                },
            ]] : []),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function messageRules(int $min = 0, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', ...($min > 0 ? ["min:{$min}"] : []), 'max:'.FormOptions::MESSAGE_MAX];
    }
}
