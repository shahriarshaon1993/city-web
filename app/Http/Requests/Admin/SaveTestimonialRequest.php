<?php

namespace App\Http\Requests\Admin;

use App\Models\Testimonial;
use App\Rules\LibraryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The testimonial editor (FR-SOC-001): a plain-text quote of 40–600 characters, no rating. */
class SaveTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $testimonial = $this->route('testimonial');

        return $testimonial instanceof Testimonial
            ? $this->user()->can('update', $testimonial)
            : $this->user()->can('create', Testimonial::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quote' => trim((string) $this->input('quote')),
            'person_name' => trim((string) $this->input('person_name')),
            'designation' => trim((string) $this->input('designation')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote' => ['required', 'string', 'min:'.Testimonial::QUOTE_MIN, 'max:'.Testimonial::QUOTE_MAX],
            'person_name' => ['required', 'string', 'min:2', 'max:'.Testimonial::NAME_MAX],
            'designation' => ['nullable', 'string', 'max:'.Testimonial::DESIGNATION_MAX],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'photo_asset_id' => ['nullable', 'integer', new LibraryImage(400, 400)],
            'is_nrb' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['person_name' => 'name', 'photo_asset_id' => 'photo', 'project_id' => 'project', 'is_nrb' => 'NRB tag'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quote.min' => 'Write at least '.Testimonial::QUOTE_MIN.' characters so the quote says something.',
            'quote.max' => 'Keep the quote to '.Testimonial::QUOTE_MAX.' characters or fewer.',
        ];
    }
}
