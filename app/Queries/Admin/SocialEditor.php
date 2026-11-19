<?php

namespace App\Queries\Admin;

use App\Enums\FaqCategory;
use App\Enums\FaqPlacement;
use App\Enums\LocationType;
use App\Enums\PartnerCategory;
use App\Enums\TeamGroup;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\FacetPage;
use App\Models\Faq;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Office;
use App\Models\Partner;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The form values, option lists and library previews of the testimonial, team, FAQ and office
 * editors (ADM-SCR-09). A form is the shape the Vue page binds to; `null` gives the defaults of a new
 * item (CRUD-03).
 */
final class SocialEditor
{
    /**
     * The library items a form points at, by id, for the field previews.
     *
     * @param  list<int|null>  $ids
     * @return array<int, array<string, mixed>>
     */
    public static function assets(Request $request, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->whereKey($ids)->with('media')->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function testimonial(?Testimonial $testimonial): array
    {
        return [
            'quote' => $testimonial->quote ?? '',
            'person_name' => $testimonial->person_name ?? '',
            'designation' => $testimonial->designation ?? '',
            'project_id' => $testimonial?->project_id,
            'photo_asset_id' => $testimonial?->photo_asset_id,
            'is_nrb' => $testimonial->is_nrb ?? false,
            'is_published' => $testimonial->is_published ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function testimonialOptions(): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project): array => ['value' => $project->id, 'label' => $project->name])->all(),
            'limits' => ['quote_min' => Testimonial::QUOTE_MIN, 'quote_max' => Testimonial::QUOTE_MAX, 'name' => Testimonial::NAME_MAX, 'designation' => Testimonial::DESIGNATION_MAX],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function team(?TeamMember $member): array
    {
        return [
            'name' => $member->name ?? '',
            'role_title' => $member->role_title ?? '',
            'group' => $member?->group->value ?? TeamGroup::Team->value,
            'photo_asset_id' => $member?->photo_asset_id,
            'bio' => $member->bio ?? '',
            'linkedin_url' => $member->linkedin_url ?? '',
            'email' => $member->email ?? '',
            'show_email' => $member->show_email ?? false,
            'is_published' => $member->is_published ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function teamOptions(): array
    {
        return [
            'groups' => array_map(fn (TeamGroup $group): array => ['value' => $group->value, 'label' => $group->label()], TeamGroup::cases()),
            'limits' => ['name' => TeamMember::NAME_MAX, 'role' => TeamMember::ROLE_MAX, 'bio' => TeamMember::BIO_MAX],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function faq(?Faq $faq): array
    {
        return [
            'question' => $faq->question ?? '',
            'answer' => $faq->answer ?? '',
            'category' => $faq?->category->value ?? FaqCategory::General->value,
            'show_on' => $faq->show_on ?? [FaqPlacement::FaqPage->value],
            'project_ids' => $faq === null ? [] : $faq->projects()->orderBy('faqables.id')->pluck('projects.id')->map(intval(...))->all(),
            'location_ids' => $faq === null ? [] : $faq->locations()->orderBy('faqables.id')->pluck('locations.id')->map(intval(...))->all(),
            'facet_ids' => $faq === null ? [] : $faq->facetPages()->orderBy('faqables.id')->pluck('facet_pages.id')->map(intval(...))->all(),
            'is_published' => $faq->is_published ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function faqOptions(): array
    {
        return [
            'categories' => array_map(fn (FaqCategory $category): array => ['value' => $category->value, 'label' => $category->label()], FaqCategory::cases()),
            'placements' => array_map(fn (FaqPlacement $placement): array => ['value' => $placement->value, 'label' => $placement->label()], FaqPlacement::cases()),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project): array => ['value' => $project->id, 'label' => $project->name])->all(),
            'locations' => Location::query()->where('type', LocationType::Area->value)->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id'])
                ->map(fn (Location $area): array => ['value' => $area->id, 'label' => $area->name.($area->parent === null ? '' : ', '.$area->parent->name)])->all(),
            'facets' => FacetPage::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'title'])
                ->map(fn (FacetPage $facet): array => ['value' => $facet->id, 'label' => $facet->title])->all(),
            'limits' => ['question' => Faq::QUESTION_MAX],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function office(?Office $office): array
    {
        return [
            'name' => $office->name ?? '',
            'address' => $office->address ?? '',
            'city' => $office->city ?? '',
            'postal_code' => $office->postal_code ?? '',
            'phones' => $office === null || $office->phones === [] ? [''] : $office->phones,
            'email' => $office->email ?? '',
            'hours' => $office->hours ?? [['days' => ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'opens' => '09:30', 'closes' => '18:30']],
            'latitude' => $office?->latitude === null ? '' : (string) $office->latitude,
            'longitude' => $office?->longitude === null ? '' : (string) $office->longitude,
            'is_head_office' => $office->is_head_office ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function officeOptions(): array
    {
        return ['days' => Office::DAYS, 'limits' => ['name' => Office::NAME_MAX]];
    }

    /**
     * Partner dialog option lists.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function partnerCategories(): array
    {
        return array_map(fn (PartnerCategory $category): array => ['value' => $category->value, 'label' => $category->label()], PartnerCategory::cases());
    }

    /**
     * What the reorder dialogs list: every item with a label and an optional group.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(TModel): array<string, mixed>  $shape
     * @return list<array<string, mixed>>
     */
    public static function orderList(string $model, callable $shape): array
    {
        return array_values($model::query()->orderBy('sort_order')->orderBy('id')->get()->map($shape)->all());
    }
}
