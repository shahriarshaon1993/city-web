<?php

namespace App\Support\Demo;

use App\Support\Format;

/**
 * Part A shortlist and compare (PG-20 / PG-21, FR-SHORT-002/003): turns the
 * browser's stored project IDs into cards and the comparison table. Unknown
 * IDs are dropped (the page then removes them from storage). Compare values
 * are formatted here with Format (the twin of lib/format.ts), so "differs"
 * compares what the visitor reads. Hidden prices stay hidden (AC-PROJ-01).
 * 13.3 swaps the rows for Project queries + Resources (same shapes,
 * types/shortlist.ts); deleted in 16.4.
 *
 * @phpstan-import-type DemoProject from DemoProjects
 * @phpstan-import-type ProjectCardData from DemoProjects
 *
 * @phpstan-type CompareRow array{key: string, label: string, values: list<string|bool|null>, differs: bool}
 * @phpstan-type CompareGroup array{key: string, heading: string|null, rows: list<CompareRow>}
 */
final class DemoShortlist
{
    /** FR-SHORT-001, same as SHORTLIST_MAX in useShortlist.ts. */
    public const int SHORTLIST_MAX = 20;

    /** FR-SHORT-003, same as COMPARE_MAX in useCompare.ts. */
    public const int COMPARE_MAX = 3;

    /**
     * PG-20 / PG-21 copy (template text; Settings in 16.x).
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'shortlist_title' => 'Your shortlist',
            'shortlist_description' => 'The projects you saved on this device.',
            'shortlist_note' => 'Saved on this device only. Clearing your browser data removes the list.',
            'shortlist_empty_title' => 'Nothing saved yet',
            'shortlist_empty_body' => 'Tap the heart on any project to keep it here and compare it later.',
            'shortlist_unavailable_body' => 'Your browser does not allow this site to save data, so the shortlist cannot be kept. Private browsing often causes this.',
            'shortlist_enquire_overline' => 'Your shortlist',
            'shortlist_enquire_title' => 'Enquire about your shortlist',
            'shortlist_enquire_text' => 'Choose the projects you want to hear about. Our sales team calls you back within one working day.',
            'shortlist_compare_hint' => 'Tick “Compare” on two or three projects to compare them.',
            'compare_title' => 'Compare projects',
            'compare_description' => 'Compare up to three Gemcon City projects side by side.',
            'compare_intro' => 'The key facts of up to three projects, side by side. Remove a project or add another at any time.',
            'compare_empty_title' => 'Choose projects to compare',
            'compare_empty_body' => 'Tick “Compare” on two or three project cards, then open the comparison from the bar at the bottom of the screen.',
            'compare_legend' => 'Tinted rows: the values differ',
            'compare_add' => 'Add a project',
            'compare_amenities' => 'Amenities',
            'price_on_request' => 'Price on request',
        ];
    }

    /**
     * `?ids=3,1,7` → distinct positive IDs in the given order, at most `$max`.
     *
     * @return list<int>
     */
    public static function ids(mixed $raw, int $max): array
    {
        if (! is_string($raw)) {
            return [];
        }

        $ids = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);

            if (preg_match('/^[1-9]\d{0,8}$/', $part) === 1 && ! in_array((int) $part, $ids, true)) {
                $ids[] = (int) $part;
            }
        }

        return array_slice($ids, 0, $max);
    }

    /**
     * Published rows for the IDs, in the IDs' order; unknown IDs are left out.
     *
     * @param  list<int>  $ids
     * @return list<DemoProject>
     */
    public static function rows(array $ids): array
    {
        $today = now(Format::TIMEZONE)->toDateString();
        $byId = [];

        foreach (DemoProjects::rows() as $row) {
            if ($row['published_at'] <= $today) {
                $byId[$row['id']] = $row;
            }
        }

        return array_values(array_filter(array_map(fn (int $id): ?array => $byId[$id] ?? null, $ids)));
    }

    /**
     * FR-SHORT-003 rows in the spec's order, then one ✓/– row per amenity any
     * of the projects has. A value of null renders as "–".
     *
     * @param  list<DemoProject>  $rows
     * @return list<CompareGroup>
     */
    public static function compare(array $rows): array
    {
        $details = array_map(fn (array $row): array => DemoProjectDetail::find($row['slug']) ?? [], $rows);
        $copy = self::copy();

        $fact = fn (string $key, string $label, callable $value): array => self::row(
            $key,
            $label,
            array_map(fn (array $detail): string|bool|null => $value($detail, $detail['facts'] ?? []), $details),
        );

        $facts = [
            $fact('segment', 'Segment', fn (array $d): string => $d['segment_label']),
            $fact('status', 'Status', fn (array $d): string => $d['lifecycle_label']),
            $fact('location', 'Location', fn (array $d): string => $d['location']['area'].', '.$d['location']['city']),
            $fact('land_area', 'Land area', fn (array $d, array $f): ?string => is_int($f['land_area_katha'] ?? null) || is_float($f['land_area_katha'] ?? null) ? Format::katha($f['land_area_katha']) : null),
            $fact('storeys', 'Storeys', fn (array $d, array $f): ?string => is_string($f['storeys'] ?? null) ? $f['storeys'] : null),
            $fact('units', 'Units', fn (array $d, array $f): ?string => is_int($f['total_units'] ?? null) ? Format::number($f['total_units']) : null),
            $fact('size', 'Size range', fn (array $d): ?string => $d['size_min_sft'] === null ? null : Format::sftRange($d['size_min_sft'], $d['size_max_sft'])),
            $fact('bedrooms', 'Bedrooms', fn (array $d): ?string => self::range($d['beds_min'], $d['beds_max'])),
            $fact('bathrooms', 'Bathrooms', fn (array $d): ?string => self::range($d['baths_min'], $d['baths_max'])),
            $fact('handover', 'Handover', fn (array $d): ?string => $d['handover_date'] === null ? null : Format::month($d['handover_date'])),
            $fact('parking', 'Car parks', fn (array $d, array $f): ?string => is_int($f['car_parks'] ?? null) ? Format::number($f['car_parks']) : null),
            $fact('lifts', 'Lifts', fn (array $d, array $f): ?string => is_int($f['lifts'] ?? null) ? Format::number($f['lifts']) : null),
            // AC-PROJ-01: price_from_bdt is already null when the project hides its price.
            $fact('price', 'Price from', fn (array $d): string => $d['price_from_bdt'] === null ? $copy['price_on_request'] : Format::bdtCompact($d['price_from_bdt'])),
        ];

        $amenities = [];

        foreach ($details as $detail) {
            foreach ($detail['amenities'] ?? [] as $amenity) {
                $amenities[$amenity['slug']] ??= $amenity['name'];
            }
        }

        $amenityRows = [];

        foreach ($amenities as $slug => $name) {
            $amenityRows[] = self::row(
                'amenity_'.$slug,
                $name,
                array_map(fn (array $detail): bool => in_array($slug, array_column($detail['amenities'] ?? [], 'slug'), true), $details),
            );
        }

        return [
            ['key' => 'facts', 'heading' => null, 'rows' => $facts],
            ['key' => 'amenities', 'heading' => $copy['compare_amenities'], 'rows' => $amenityRows],
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
