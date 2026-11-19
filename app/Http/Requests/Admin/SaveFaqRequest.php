<?php

namespace App\Http\Requests\Admin;

use App\Actions\Social\SaveFaq;
use App\Enums\FaqCategory;
use App\Enums\FaqPlacement;
use App\Models\Faq;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The FAQ editor (FR-SOC-003): a question of up to 160 characters and a limited-rich-text answer. */
class SaveFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        $faq = $this->route('faq');

        return $faq instanceof Faq
            ? $this->user()->can('update', $faq)
            : $this->user()->can('create', Faq::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['question' => trim((string) $this->input('question'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:5', 'max:'.Faq::QUESTION_MAX],
            'answer' => ['required', 'string', 'max:20000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (trim(strip_tags(SaveFaq::cleanAnswer((string) $value))) === '') {
                    $fail('Write the answer.');
                }
            }],
            'category' => ['required', Rule::enum(FaqCategory::class)],
            'show_on' => ['nullable', 'array', 'max:'.count(FaqPlacement::cases())],
            'show_on.*' => ['string', Rule::enum(FaqPlacement::class), 'distinct'],
            'project_ids' => ['nullable', 'array', 'max:50'],
            'project_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'location_ids' => ['nullable', 'array', 'max:50'],
            'location_ids.*' => ['integer', 'distinct', Rule::exists('locations', 'id')->whereNull('deleted_at')],
            'facet_ids' => ['nullable', 'array', 'max:8'],
            'facet_ids.*' => ['integer', 'distinct', Rule::exists('facet_pages', 'id')],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['show_on' => 'places', 'project_ids' => 'projects', 'project_ids.*' => 'project', 'location_ids' => 'areas', 'location_ids.*' => 'area', 'facet_ids' => 'listing pages', 'facet_ids.*' => 'listing page'];
    }
}
