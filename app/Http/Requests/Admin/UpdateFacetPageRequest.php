<?php

namespace App\Http\Requests\Admin;

use App\Models\FacetPage;
use App\Support\Seo\SeoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** The facet landing page editor (ADM-SCR-05): H1, intro and SEO of one fixed row. */
class UpdateFacetPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $facet = $this->route('facet');

        return $facet instanceof FacetPage && $this->user()->can('update', $facet);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:'.FacetPage::TITLE_MAX],
            'intro' => ['nullable', 'string', 'max:60000'],
            ...SeoRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'heading', ...SeoRules::attributes()];
    }
}
