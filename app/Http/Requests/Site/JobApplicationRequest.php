<?php

namespace App\Http\Requests\Site;

use App\Http\Requests\Site\Concerns\ContactFieldRules;
use App\Support\Demo\DemoCareers;
use App\Support\Forms\FormOptions;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-CAR-002 apply form (PG-16) and the general CV drop of PG-15 (FR-CAR-004,
 * no `job`). The CV is checked by content and extension (PDF/DOC/DOCX, 5 MB,
 * AC-CAR-01); it goes to the private disk from 17.1 on.
 */
class JobApplicationRequest extends FormRequest
{
    use ContactFieldRules {
        prepareForValidation as prepareContactFields;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A LinkedIn link typed without its scheme (`linkedin.com/in/…`) gets `https://`.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareContactFields();

        $url = $this->input('linkedin_url');

        if (is_string($url) && trim($url) !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', trim($url))) {
            $this->merge(['linkedin_url' => 'https://'.trim($url)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // FR-CAR-003: only open roles take applications.
            'job' => ['nullable', 'string', Rule::in(array_column(DemoCareers::open(), 'slug'))],
            ...$this->personRules(email: true),
            'current_position' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:'.FormOptions::YEARS_EXPERIENCE_MAX],
            'expected_salary' => ['nullable', 'string', 'max:40'],
            'linkedin_url' => [
                'nullable',
                'string',
                'max:255',
                'url:http,https',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));

                    if ($host !== 'linkedin.com' && ! str_ends_with($host, '.linkedin.com')) {
                        $fail('validation.custom.linkedin_url.url')->translate();
                    }
                },
            ],
            'cv' => [
                'required',
                'file',
                // AC-CAR-01: an oversized CV reports its size first.
                'max:'.FormOptions::MAX_FILE_KB,
                'mimes:'.implode(',', FormOptions::CV_EXTENSIONS),
                'extensions:'.implode(',', FormOptions::CV_EXTENSIONS),
            ],
            'cover_letter' => ['nullable', 'string', 'max:'.FormOptions::COVER_LETTER_MAX],
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
