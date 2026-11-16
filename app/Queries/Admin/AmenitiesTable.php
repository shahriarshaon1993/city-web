<?php

namespace App\Queries\Admin;

use App\Enums\AmenityCategory;
use App\Http\Resources\Admin\AmenityRowResource;
use App\Models\Amenity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-03 amenities: a small table (dozens of rows) in the library order, with the number of
 * projects that list each one.
 *
 * @extends AdminTable<Amenity>
 */
final class AmenitiesTable extends AdminTable
{
    public const string KEY = 'amenities';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['catalog.view'];
    }

    public function url(): string
    {
        return route('admin.amenities.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Amenity::query()->withCount('projects');
    }

    protected function filters(): array
    {
        $allowed = array_map(fn (AmenityCategory $category): string => $category->value, AmenityCategory::cases());

        return [
            AllowedFilter::callback('category', function (Builder $query, mixed $value) use ($allowed): void {
                $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $allowed));

                if ($values !== []) {
                    $query->whereIn('category', $values);
                }
            }),
        ];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'name', 'category', 'projects_count'];
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    protected function resource(): string
    {
        return AmenityRowResource::class;
    }

    protected function defaultPerPage(): int
    {
        return 50;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Amenity', 'value' => fn (Amenity $amenity): string => $amenity->name],
            'category' => ['label' => 'Category', 'value' => fn (Amenity $amenity): string => $amenity->category->label()],
            'icon' => ['label' => 'Icon', 'value' => fn (Amenity $amenity): string => $amenity->icon],
            'projects_count' => ['label' => 'Projects', 'value' => fn (Amenity $amenity): int => (int) $amenity->getAttribute('projects_count')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $totals = Amenity::query()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');

        return [
            'category' => array_map(fn (AmenityCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
                'count' => (int) ($totals[$category->value] ?? 0),
            ], AmenityCategory::cases()),
        ];
    }
}
