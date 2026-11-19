<?php

namespace App\Queries\Site;

use App\Support\Catalog\CatalogCopy;
use App\Support\Format;
use App\Support\Shortlist\ShortlistCopy;

/**
 * PG-20 / PG-21 (FR-SHORT-002/003): turns the browser's stored project IDs into cards and the
 * comparison table. Unknown or unpublished IDs are dropped (the page then removes them from storage).
 * Compare values are formatted with `Format` (the twin of lib/format.ts), so "differs" compares what
 * the visitor reads. Hidden prices stay hidden (AC-PROJ-01): the detail props already carry none.
 *
 * @phpstan-type CompareRow array{key: string, label: string, values: list<string|bool|null>, differs: bool}
 * @phpstan-type CompareGroup array{key: string, heading: string|null, rows: list<CompareRow>}
 */
final class ShortlistProjects
{
    /**
     * Cards of the published projects among the IDs, in the IDs' order.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public static function cards(array $ids): array
    {
        return ProjectCards::byIds($ids);
    }

    /**
     * FR-SHORT-003 rows in the spec's order, then one ✓/– row per amenity any of the projects has.
     * A value of null renders as "–".
     *
     * @param  list<array<string, mixed>>  $cards  from {@see self::cards()}
     * @return list<CompareGroup>
     */
    public static function compare(array $cards): array
    {
        $details = array_values(array_filter(array_map(
            fn (array $card): ?array => ProjectDetail::find($card['slug'])['project'] ?? null,
            $cards,
        )));

        $fact = fn (string $key, string $label, callable $value): array => self::row(
            $key,
            $label,
            array_map(fn (array $detail): string|bool|null => $value($detail, $detail['facts']), $details),
        );

        $facts = [
            $fact('segment', 'Segment', fn (array $d): string => $d['segment_label']),
            $fact('status', 'Status', fn (array $d): string => $d['lifecycle_label']),
            $fact('location', 'Location', fn (array $d): string => $d['location']['area'].', '.$d['location']['city']),
            $fact('land_area', 'Land area', fn (array $d, array $f): ?string => is_int($f['land_area_katha']) || is_float($f['land_area_katha']) ? Format::katha($f['land_area_katha']) : null),
            $fact('storeys', 'Storeys', fn (array $d, array $f): ?string => is_string($f['storeys']) ? $f['storeys'] : null),
            $fact('units', 'Units', fn (array $d, array $f): ?string => is_int($f['total_units']) ? Format::number($f['total_units']) : null),
            $fact('size', 'Size range', fn (array $d): ?string => $d['size_min_sft'] === null ? null : Format::sftRange($d['size_min_sft'], $d['size_max_sft'])),
            $fact('bedrooms', 'Bedrooms', fn (array $d): ?string => self::range($d['beds_min'], $d['beds_max'])),
            $fact('bathrooms', 'Bathrooms', fn (array $d): ?string => self::range($d['baths_min'], $d['baths_max'])),
            $fact('handover', 'Handover', fn (array $d): ?string => $d['handover_date'] === null ? null : Format::month($d['handover_date'])),
            $fact('parking', 'Car parks', fn (array $d, array $f): ?string => is_int($f['car_parks']) ? Format::number($f['car_parks']) : null),
            $fact('lifts', 'Lifts', fn (array $d, array $f): ?string => is_int($f['lifts']) ? Format::number($f['lifts']) : null),
            // AC-PROJ-01: price_from_bdt is already null when the project hides its price.
            $fact('price', 'Price from', fn (array $d): string => $d['price_from_bdt'] === null ? CatalogCopy::PRICE_ON_REQUEST : Format::bdtCompact($d['price_from_bdt'])),
        ];

        $amenities = [];

        foreach ($details as $detail) {
            foreach ($detail['amenities'] as $amenity) {
                $amenities[$amenity['slug']] ??= $amenity['name'];
            }
        }

        $amenityRows = [];

        foreach ($amenities as $slug => $name) {
            $amenityRows[] = self::row(
                'amenity_'.$slug,
                $name,
                array_map(fn (array $detail): bool => in_array($slug, array_column($detail['amenities'], 'slug'), true), $details),
            );
        }

        return [
            ['key' => 'facts', 'heading' => null, 'rows' => $facts],
            ['key' => 'amenities', 'heading' => ShortlistCopy::copy()['compare_amenities'], 'rows' => $amenityRows],
        ];
    }

    /**
     * @param  list<string|bool|null>  $values
     * @return CompareRow
     */
    private static function row(string $key, string $label, array $values): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'values' => $values,
            'differs' => count(array_unique(array_map(fn (string|bool|null $value): string => var_export($value, true), $values))) > 1,
        ];
    }

    private static function range(?int $min, ?int $max): ?string
    {
        if ($min === null) {
            return null;
        }

        return $max === null || $max === $min ? (string) $min : $min.'–'.$max;
    }
}
