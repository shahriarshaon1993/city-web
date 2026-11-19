<?php

namespace App\Queries\Site;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Support\Search\ProjectFilters;
use App\Support\Settings\Settings;

/**
 * CMP-SEARCH-01 options (`ProjectSearchOptions` in types/search.ts): values are FR-SRCH-002 query values,
 * `url` is the clean landing page a single facet goes to (HERO-041, FR-SRCH-008).
 */
final class ProjectSearchOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $option = fn (string $value, string $label, ?string $url): array => ['value' => $value, 'label' => $label, 'url' => $url];
        // Facet URL slugs use hyphens (`handed-over`, owner in 4.1).
        $slug = ProjectFilters::urlSlug(...);

        $cities = [];

        foreach (AreaDirectory::all() as $area) {
            $cities[$area['city']][] = $option($area['slug'], $area['name'], '/locations/'.$area['slug']);
        }

        return [
            'action' => '/projects',
            'segments' => array_map(
                fn (string $value): array => $option($value, Segment::from($value)->label(), '/projects/type/'.$slug($value)),
                ['residential', 'commercial', 'land'],
            ),
            'statuses' => array_map(
                fn (string $value): array => $option($value, Lifecycle::from($value)->label(), '/projects/status/'.$slug($value)),
                array_map(fn (Lifecycle $lifecycle): string => $lifecycle->value, Lifecycle::cases()),
            ),
            'cities' => array_map(
                fn (string $city, array $cityAreas): array => ['city' => $city, 'areas' => $cityAreas],
                array_keys($cities),
                array_values($cities),
            ),
            // DEC-42: one `size` value "{min}-{max}" so the no-JS form can submit it.
            'sizes' => array_map(
                fn (array $band): array => [
                    'value' => $band['min'].'-'.$band['max'],
                    'label' => $band['label'],
                    'min' => $band['min'],
                    'max' => $band['max'],
                ],
                Settings::group('search')['size_bands'],
            ),
            'beds' => array_map(
                fn (int $beds): array => $option((string) $beds, $beds === 5 ? '5+ beds' : $beds.' '.($beds === 1 ? 'bed' : 'beds'), null),
                range(1, 5),
            ),
        ];
    }
}
