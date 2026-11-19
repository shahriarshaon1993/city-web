<?php

namespace App\Queries\Admin;

use App\Enums\PartnerCategory;
use App\Http\Resources\Admin\PartnerRowResource;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-09 partners: the logos in marquee order, with the Published switch.
 *
 * @extends SocialTable<Partner>
 */
final class PartnersTable extends SocialTable
{
    public const string KEY = 'partners';

    public function key(): string
    {
        return self::KEY;
    }

    public function url(): string
    {
        return route('admin.partners.index');
    }

    protected function model(): string
    {
        return Partner::class;
    }

    protected function baseQuery(User $actor): Builder
    {
        return Partner::query()->with('logo.media');
    }

    protected function filters(): array
    {
        return [$this->statusFilter(), $this->enumFilter('category', 'category', PartnerCategory::class)];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'name', 'category', 'is_published'];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('name', 'like', $this->likeTerm($term));
    }

    protected function resource(): string
    {
        return PartnerRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Partner', 'value' => fn (Partner $partner): string => $partner->name],
            'category' => ['label' => 'Category', 'value' => fn (Partner $partner): string => $partner->category->label()],
            'url' => ['label' => 'Website', 'value' => fn (Partner $partner): ?string => $partner->url],
            'is_published' => ['label' => 'Published', 'value' => fn (Partner $partner): string => $partner->is_published ? 'Yes' : 'No'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return ['status' => $this->statusOptions(), 'category' => $this->enumOptions('category', PartnerCategory::class)];
    }
}
