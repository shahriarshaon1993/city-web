<?php

namespace App\Support\Blocks\Schemas;

use App\Queries\Site\AreaDirectory;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Catalog\CatalogCopy;
use Illuminate\Validation\Rule;

/** Block 24, CMP-BLK-LOCATIONS: heading, optional city filter, limit; the busiest areas first. */
final class LocationsGridSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'city' => ['nullable', 'string', Rule::exists('locations', 'slug')->whereNull('deleted_at')->where('type', 'city')],
            'limit' => ['required', 'integer', 'between:1,24'],
        ];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $city = ($data['city'] ?? '') === '' ? null : $data['city'];
        $areas = array_values(array_filter(BlockData::areas(), fn (array $area): bool => $city === null || $area['city_slug'] === $city));
        usort($areas, fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $images = AreaDirectory::images();

        return [
            'heading' => $data['heading'] ?? null,
            'areas' => array_map(fn (array $area): array => [
                'slug' => $area['slug'],
                'name' => $area['name'],
                'url' => route('site.locations.show', $area['slug'], false),
                'count' => $area['count'],
                'image' => $images[$area['slug']] ?? null,
            ], array_slice($areas, 0, (int) ($data['limit'] ?? 7))),
            'no_projects' => CatalogCopy::locations()['no_projects'],
            'view_all' => ['label' => 'All locations', 'url' => '/locations', 'new_tab' => false],
        ];
    }
}
