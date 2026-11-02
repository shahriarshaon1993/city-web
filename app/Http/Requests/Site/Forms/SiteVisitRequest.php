<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Validation\Rule;

/**
 * `site_visit`: preferred date tomorrow … +60 days in Dhaka time (Fridays
 * allowed), a time slot and 1–10 visitors.
 */
class SiteVisitRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::SiteVisit;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->projectRules(required: true),
            ...$this->personRules(),
            'visit_date' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $window = FormOptions::visitWindow();
                    $date = CarbonImmutable::createFromFormat('Y-m-d', (string) $value, FormOptions::TIMEZONE);

                    if ($date === null || $date->startOfDay()->lt($window['min']) || $date->startOfDay()->gt($window['max'])) {
                        $fail('validation.visit_window')->translate([
                            'min' => $window['min']->format('j M Y'),
                            'max' => $window['max']->format('j M Y'),
                        ]);
                    }
                },
            ],
            'visit_slot' => ['required', Rule::in(FormOptions::values('contact_time'))],
            'visitors' => ['nullable', 'integer', 'min:1', 'max:10'],
            'message' => $this->messageRules(),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
