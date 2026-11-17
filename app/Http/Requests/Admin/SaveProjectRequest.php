<?php

namespace App\Http\Requests\Admin;

use App\Enums\Availability;
use App\Enums\BookingState;
use App\Enums\DocumentKind;
use App\Enums\GalleryCategory;
use App\Enums\Lifecycle;
use App\Enums\NearbyCategory;
use App\Enums\ProgressStage;
use App\Enums\Segment;
use App\Enums\TravelMode;
use App\Enums\VideoType;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectNearbyPlace;
use App\Models\ProjectUnitType;
use App\Rules\UniqueSlug;
use App\Support\Media\MediaKind;
use App\Support\Seo\SeoRules;
use App\Support\Slugs\SlugAvailability;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The project editor (ADM-SCR-01) with the exact rules of spec/projects.md. Saving a draft is
 * blocked only by format rules; the publish-only rules (cover, 4 gallery images, overview length,
 * …) are the checklist of `PublishEntity` (VAL-05). Image size minimums apply to newly chosen
 * images only, so an older project always saves.
 */
class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            ? $this->user()->can('update', $project)
            : $this->user()->can('create', Project::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => trim((string) $this->input('slug')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $id = $project instanceof Project ? $project->id : null;
        $current = $project instanceof Project ? $this->currentImageIds($project) : [];
        $assetExists = Rule::exists('media_assets', 'id');
        $imageExists = Rule::exists('media_assets', 'id')->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value]);
        $slugRules = ['required', 'string', 'max:'.Project::SLUG_MAX, 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(Project::class, $id, 'project')];

        $handover = ['nullable', 'date_format:Y-m'];

        if ($this->filled('launch_date')) {
            $handover[] = 'after_or_equal:launch_date';
        }

        return [
            'name' => ['required', 'string', 'min:3', 'max:'.Project::NAME_MAX, Rule::unique('projects', 'name')->whereNull('deleted_at')->ignore($id)],
            'slug' => $slugRules,
            'tagline' => ['nullable', 'string', 'max:120'],
            'segment' => ['required', Rule::enum(Segment::class)],
            'lifecycle' => ['required', Rule::enum(Lifecycle::class)],
            'booking_state' => ['required', Rule::enum(BookingState::class)],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('type', 'area')->whereNull('deleted_at')],
            'is_featured' => ['boolean'],

            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:20.5,26.7'],
            'longitude' => ['nullable', 'numeric', 'between:88,92.7'],
            'land_area_katha' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'storeys' => ['nullable', 'string', 'max:40'],
            'total_units' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'units_per_floor' => ['nullable', 'integer', 'min:0', 'max:255'],
            'lifts' => ['nullable', 'integer', 'min:0', 'max:255'],
            'car_parks' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'basements' => ['nullable', 'integer', 'min:0', 'max:255'],
            'road_width_ft' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'facing' => ['nullable', 'string', 'max:40'],
            'orientation' => ['nullable', 'string', 'max:40'],
            'launch_date' => ['nullable', 'date_format:Y-m'],
            'handover_date' => $handover,
            'rajuk_approval_no' => ['nullable', 'string', 'max:80'],
            'architect' => ['nullable', 'string', 'max:150'],
            'structural_engineer' => ['nullable', 'string', 'max:150'],
            'contractor' => ['nullable', 'string', 'max:150'],
            'custom_facts' => ['nullable', 'array', 'max:'.Project::CUSTOM_FACTS_MAX],
            'custom_facts.*.label' => ['nullable', 'string', 'max:60'],
            'custom_facts.*.value' => ['nullable', 'string', 'max:120'],

            'overview' => ['nullable', 'string', 'max:60000'],
            'design_narrative' => ['nullable', 'string', 'max:60000'],
            'highlights' => ['nullable', 'array', 'max:'.Project::HIGHLIGHTS_MAX],
            'highlights.*.asset_id' => ['required', 'integer', $imageExists],
            'highlights.*.title' => ['required', 'string', 'max:80'],
            'highlights.*.text' => ['nullable', 'string', 'max:300'],

            'cover_asset_id' => ['nullable', 'integer', $imageExists, $this->minWidth(1600, 'cover image', $current)],
            'gallery' => ['nullable', 'array', 'max:'.Project::GALLERY_MAX],
            'gallery.*.asset_id' => ['required', 'integer', 'distinct', $imageExists, $this->minWidth(1200, 'gallery image', $current)],
            'gallery.*.category' => ['required', Rule::enum(GalleryCategory::class)],
            'gallery.*.caption' => ['nullable', 'string', 'max:200'],
            'video_type' => ['required', Rule::enum(VideoType::class)],
            'video_url' => [
                'nullable',
                'required_if:video_type,youtube',
                'string',
                'max:255',
                'regex:~^https://(www\.youtube\.com/(watch\?v=|shorts/)|youtu\.be/)[A-Za-z0-9_-]{6,}~',
            ],
            'video_asset_id' => ['nullable', 'required_if:video_type,upload', 'integer', Rule::exists('media_assets', 'id')->where('kind', MediaKind::Video->value)],
            'video_poster_asset_id' => ['nullable', 'integer', $imageExists],
            'video_captions_asset_id' => ['nullable', 'integer', $assetExists],
            'virtual_tour_url' => ['nullable', 'string', 'max:255', 'regex:~^https://(my\.matterport\.com|kuula\.co|www\.youtube\.com)/~'],

            'unit_types' => ['nullable', 'array', 'max:'.ProjectUnitType::MAX_PER_PROJECT],
            'unit_types.*.id' => ['nullable', 'integer'],
            'unit_types.*.name' => ['required', 'string', 'max:60'],
            'unit_types.*.size_sft' => ['required', 'integer', 'min:1', 'max:4000000'],
            'unit_types.*.size_sft_max' => ['nullable', 'integer', 'min:1', 'max:4000000', 'gte:unit_types.*.size_sft'],
            'unit_types.*.bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'unit_types.*.bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'unit_types.*.balconies' => ['nullable', 'integer', 'min:0', 'max:20'],
            'unit_types.*.facing' => ['nullable', 'string', 'max:40'],
            'unit_types.*.floor_range' => ['nullable', 'string', 'max:60'],
            'unit_types.*.floor_plan_asset_id' => ['nullable', 'integer', $imageExists],
            'unit_types.*.plan_description' => ['nullable', 'string', 'max:300'],
            'unit_types.*.plan_3d_asset_id' => ['nullable', 'integer', $imageExists],
            'unit_types.*.price_bdt' => ['nullable', 'integer', 'min:'.ProjectUnitType::PRICE_MIN, 'max:'.ProjectUnitType::PRICE_MAX],
            'unit_types.*.show_price' => ['boolean'],
            'unit_types.*.availability' => ['required', Rule::enum(Availability::class)],

            'amenity_ids' => ['nullable', 'array', 'max:80'],
            'amenity_ids.*' => ['integer', 'distinct', Rule::exists('amenities', 'id')->whereNull('deleted_at')],

            'progress_updates' => ['nullable', 'array', 'max:60'],
            'progress_updates.*.id' => ['nullable', 'integer'],
            'progress_updates.*.reported_on' => ['required', 'date_format:Y-m'],
            'progress_updates.*.percent' => ['required', 'integer', 'min:0', 'max:100'],
            'progress_updates.*.stage' => ['required', Rule::enum(ProgressStage::class)],
            'progress_updates.*.note' => ['nullable', 'string', 'max:300'],
            'progress_updates.*.photo_ids' => ['nullable', 'array', 'max:12'],
            'progress_updates.*.photo_ids.*' => ['integer', $imageExists],

            'nearby_places' => ['nullable', 'array', 'max:'.ProjectNearbyPlace::MAX_PER_PROJECT],
            'nearby_places.*.name' => ['required', 'string', 'max:100'],
            'nearby_places.*.category' => ['required', Rule::enum(NearbyCategory::class)],
            'nearby_places.*.minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'nearby_places.*.mode' => ['required', Rule::enum(TravelMode::class)],

            'documents' => ['nullable', 'array', 'max:'.ProjectDocument::MAX_PER_PROJECT],
            'documents.*.asset_id' => ['required', 'integer', Rule::exists('media_assets', 'id')->where('kind', MediaKind::Document->value)],
            'documents.*.title' => ['required', 'string', 'max:100'],
            'documents.*.kind' => ['required', Rule::enum(DocumentKind::class)],
            'documents.*.is_gated' => ['boolean'],
            'brochure_gated' => ['boolean'],

            'show_price' => ['boolean'],
            'price_from_manual' => ['boolean'],
            'price_from_bdt' => ['nullable', 'integer', 'min:'.ProjectUnitType::PRICE_MIN, 'max:'.ProjectUnitType::PRICE_MAX],
            'price_note' => ['nullable', 'string', 'max:80'],

            'default_assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'whatsapp_override' => ['nullable', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'similar_ids' => ['nullable', 'array', 'max:'.Project::SIMILAR_MAX],
            'similar_ids.*' => ['integer', 'distinct', Rule::exists('projects', 'id')->whereNull('deleted_at'), ...($id === null ? [] : [Rule::notIn([$id])])],

            ...SeoRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Another project already has this name.',
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. gemcon-orchid.',
            'latitude.between' => 'Latitude must be inside Bangladesh (20.5 to 26.7).',
            'longitude.between' => 'Longitude must be inside Bangladesh (88.0 to 92.7).',
            'handover_date.after_or_equal' => 'Handover cannot be before the launch date.',
            'video_url.regex' => 'Paste a YouTube link like https://www.youtube.com/watch?v=… or https://youtu.be/….',
            'virtual_tour_url.regex' => 'Use a link from my.matterport.com, kuula.co or www.youtube.com.',
            'whatsapp_override.regex' => 'Enter the number with country code, e.g. +8801712345678.',
            'unit_types.*.size_sft_max.gte' => 'The largest size cannot be smaller than the size.',
            'unit_types.*.price_bdt.min' => 'A price must be at least ৳ 1,00,000.',
            'unit_types.*.price_bdt.max' => 'A price cannot be more than ৳ 1,000 crore.',
            'price_from_bdt.min' => 'A price must be at least ৳ 1,00,000.',
            'price_from_bdt.max' => 'A price cannot be more than ৳ 1,000 crore.',
            'unit_types.max' => 'A project holds up to 20 unit types.',
            'gallery.max' => 'The gallery holds up to 60 images.',
            'highlights.max' => 'Up to 8 feature highlights.',
            'custom_facts.max' => 'Up to 12 custom facts.',
            'nearby_places.max' => 'Up to 10 nearby places.',
            'documents.max' => 'Up to 5 documents.',
            'similar_ids.max' => 'Pick up to 3 similar projects.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'location_id' => 'location',
            'land_area_katha' => 'land area (katha)',
            'road_width_ft' => 'road width',
            'cover_asset_id' => 'cover image',
            'video_url' => 'video link',
            'video_asset_id' => 'video file',
            'virtual_tour_url' => 'virtual tour link',
            'unit_types.*.name' => 'unit type name',
            'unit_types.*.size_sft' => 'size (sft)',
            'unit_types.*.size_sft_max' => 'largest size (sft)',
            'unit_types.*.price_bdt' => 'price',
            'unit_types.*.floor_plan_asset_id' => 'floor plan',
            'progress_updates.*.reported_on' => 'month',
            'progress_updates.*.percent' => 'percent',
            'nearby_places.*.name' => 'place name',
            'nearby_places.*.minutes' => 'travel time',
            'documents.*.asset_id' => 'document file',
            'default_assignee_id' => 'default assignee',
            'whatsapp_override' => 'WhatsApp number',
            ...SeoRules::attributes(),
        ];
    }

    /**
     * Images the project already holds are not re-checked.
     *
     * @return list<int>
     */
    private function currentImageIds(Project $project): array
    {
        return array_values(array_filter([$project->cover_asset_id, ...array_column($project->gallery ?? [], 'asset_id')]));
    }

    /**
     * @param  list<int>  $current
     */
    private function minWidth(int $pixels, string $noun, array $current): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($pixels, $noun, $current): void {
            if (in_array((int) $value, $current, true)) {
                return;
            }

            $asset = MediaAsset::query()->whereKey((int) $value)->first();

            if ($asset !== null && $asset->kind === MediaKind::Image && $asset->width !== null && $asset->width < $pixels) {
                $fail("This {$noun} is {$asset->width} px wide; it must be at least {$pixels} px wide.");
            }
        };
    }
}
