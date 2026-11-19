<?php

namespace App\Http\Requests\Admin;

use App\Enums\PartnerCategory;
use App\Models\Partner;
use App\Rules\LibraryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The partner dialog (FR-SOC-002): name, logo (SVG, or an image at least 300 px wide), link and category. */
class SavePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $partner = $this->route('partner');

        return $partner instanceof Partner
            ? $this->user()->can('update', $partner)
            : $this->user()->can('create', Partner::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'url' => trim((string) $this->input('url'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:'.Partner::NAME_MAX],
            'logo_asset_id' => ['required', 'integer', new LibraryImage(300, allowSvg: true)],
            'url' => ['nullable', 'string', 'max:255', 'url:https,http'],
            'category' => ['required', Rule::enum(PartnerCategory::class)],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['logo_asset_id' => 'logo', 'url' => 'website'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo_asset_id.required' => 'Choose the logo. An SVG or a transparent PNG works best.',
            'url.url' => 'Enter a full web address, e.g. https://www.example.com.',
        ];
    }
}
