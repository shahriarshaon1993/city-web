<?php

namespace App\Http\Requests\Admin;

use App\Enums\Segment;
use App\Models\Location;
use App\Rules\UniqueSlug;
use App\Support\Media\MediaKind;
use App\Support\Seo\SeoRules;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The area editor (ADM-SCR-04, FR-LOC-002): intro, facts, map centre, nearby areas and SEO. */
class SaveAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $area = $this->route('location');

        return $area instanceof Location
            ? $this->user()->can('update', $area)
            : $this->user()->can('create', Location::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'slug' => trim((string) $this->input('slug'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $area = $this->route('location');
        $id = $area instanceof Location ? $area->id : null;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(Location::class, $id, 'city or area')],
            'parent_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('type', 'city')->whereNull('deleted_at')],
            'intro' => ['nullable', 'string', 'max:60000'],
            'segment_intros' => ['nullable', 'array'],
            ...collect(Segment::cases())->mapWithKeys(fn (Segment $segment): array => ["segment_intros.{$segment->value}" => ['nullable', 'string', 'max:20000']])->all(),
            'facts' => ['nullable', 'array', 'max:'.Location::FACTS_MAX],
            'facts.*.label' => ['required', 'string', 'max:60'],
            'facts.*.value' => ['required', 'string', 'max:120'],
            'hero_asset_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])],
            'latitude' => ['nullable', 'numeric', 'between:20.5,26.7'],
            'longitude' => ['nullable', 'numeric', 'between:88,92.7'],
            'zoom' => ['required', 'integer', 'between:'.Location::ZOOM_MIN.','.Location::ZOOM_MAX],
            'nearby_ids' => ['nullable', 'array', 'max:'.Location::NEARBY_MAX],
            'nearby_ids.*' => ['integer', 'distinct', Rule::exists('locations', 'id')->where('type', 'area')->whereNull('deleted_at'), ...($id === null ? [] : [Rule::notIn([$id])])],
            ...SeoRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. bashundhara-ra.',
            'parent_id.required' => 'Choose the city this area belongs to.',
            'latitude.between' => 'Latitude must be inside Bangladesh (20.5 to 26.7).',
            'longitude.between' => 'Longitude must be inside Bangladesh (88.0 to 92.7).',
            'facts.max' => 'Up to 8 facts.',
            'nearby_ids.max' => 'Pick up to 6 nearby areas.',
            'zoom.between' => 'Zoom is between 11 and 16.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['parent_id' => 'city', 'hero_asset_id' => 'hero image', 'nearby_ids.*' => 'nearby area', ...SeoRules::attributes()];
    }
}
