<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Rules\UniqueSlug;
use App\Support\Careers\JobDates;
use App\Support\Seo\SeoRules;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The job post editor (ADM-SCR-10, FR-CAR-001). Format rules block every save; what an Open role needs
 * (a future deadline, a description) is checked here too because Open is the only status the public sees
 * (VAL-05): a Draft or a Closed role saves with whatever it has.
 */
class SaveJobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof JobPost
            ? $this->user()->can('update', $job)
            : $this->user()->can('create', JobPost::class);
    }

    protected function prepareForValidation(): void
    {
        $text = fn (string $key): string => trim((string) $this->input($key));
        $number = fn (string $key): mixed => preg_match('/^\d+$/', preg_replace('/[\s,]/', '', $text($key)) ?? '') === 1
            ? (int) preg_replace('/[\s,]/', '', $text($key))
            : ($text($key) === '' ? null : $text($key));

        $this->merge([
            'title' => $text('title'),
            'slug' => $text('slug'),
            'department' => $text('department'),
            'summary' => $text('summary'),
            'experience' => $text('experience'),
            'salary_text' => $text('salary_text'),
            'salary_min_bdt' => $number('salary_min_bdt'),
            'salary_max_bdt' => $number('salary_max_bdt'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $job = $this->route('job');
        $id = $job instanceof JobPost ? $job->id : null;
        $opening = $this->input('status') === JobStatus::Open->value;
        $html = ['nullable', 'string', 'max:60000'];

        return [
            'title' => ['required', 'string', 'min:3', 'max:'.JobPost::TITLE_MAX],
            'slug' => ['required', 'string', 'max:'.JobPost::TITLE_MAX, 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(JobPost::class, $id, 'job post')],
            'department' => ['required', 'string', 'max:60'],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')->whereNull('deleted_at')],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'experience' => ['nullable', 'string', 'max:80'],
            'salary_text' => ['nullable', 'string', 'max:80'],
            'salary_min_bdt' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'salary_max_bdt' => ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:salary_min_bdt'],
            'summary' => ['required', 'string', 'min:10', 'max:'.JobPost::SUMMARY_MAX],
            'description' => $html,
            'responsibilities' => $html,
            'requirements' => $html,
            'benefits' => $html,
            'deadline' => ['required', 'date_format:Y-m-d', $opening ? 'after_or_equal:'.JobDates::today()->toDateString() : 'after:2000-01-01', 'before:'.JobDates::today()->addYears(2)->toDateString()],
            'status' => ['required', Rule::enum(JobStatus::class)],
            ...SeoRules::rules(),
        ];
    }

    /**
     * What an Open role needs beyond the format rules (VAL-05). An editor that was cleared leaves
     * `<p></p>`, so "empty" means no visible text.
     *
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('status') !== JobStatus::Open->value) {
                return;
            }

            $empty = fn (string $key): bool => trim(strip_tags((string) $this->input($key))) === '';

            if ($empty('description')) {
                $validator->errors()->add('description', 'Add a description of the role before opening it.');
            }

            if ($empty('responsibilities') && $empty('requirements')) {
                $validator->errors()->add('responsibilities', 'Add the responsibilities or the requirements before opening the role.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. senior-sales-executive.',
            'title.max' => 'The title can be at most '.JobPost::TITLE_MAX.' characters.',
            'summary.min' => 'Write one sentence that says what the role is.',
            'summary.max' => 'The summary can be at most '.JobPost::SUMMARY_MAX.' characters.',
            'summary.required' => 'Write one sentence that says what the role is.',
            'deadline.after_or_equal' => 'An open role needs a deadline of today or later. Move the deadline, or save it as closed.',
            'deadline.before' => 'The deadline can be at most two years away.',
            'salary_max_bdt.gte' => 'The highest salary cannot be lower than the lowest.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'office_id' => 'location',
            'employment_type' => 'employment type',
            'salary_text' => 'salary',
            'salary_min_bdt' => 'lowest salary',
            'salary_max_bdt' => 'highest salary',
            ...SeoRules::attributes(),
        ];
    }
}
