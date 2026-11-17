<?php

namespace App\Support\Projects;

use App\Support\Html\BasicHtml;
use Illuminate\Support\Carbon;

/**
 * The editor payload of `SaveProjectRequest` turned into database columns, in one place: `SaveProject`
 * writes the result, `ProjectHydrator` (preview, publish checklist, revision snapshots) builds
 * unsaved models from it, so what a visitor previews is exactly what Publish later stores.
 */
final class ProjectAttributes
{
    /**
     * Columns of the project row itself.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromPayload(array $data): array
    {
        $text = fn (string $key): ?string => self::text($data[$key] ?? null);
        $number = fn (string $key): int|float|null => ($data[$key] ?? '') === '' ? null : $data[$key] + 0;

        $attributes = [
            'name' => trim((string) $data['name']),
            'slug' => (string) $data['slug'],
            'tagline' => $text('tagline'),
            'segment' => $data['segment'],
            'lifecycle' => $data['lifecycle'],
            'booking_state' => $data['booking_state'],
            'location_id' => $data['location_id'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'address' => $text('address'),
            'latitude' => $number('latitude'),
            'longitude' => $number('longitude'),
            'land_area_katha' => $number('land_area_katha'),
            'storeys' => $text('storeys'),
            'total_units' => $number('total_units'),
            'units_per_floor' => $number('units_per_floor'),
            'lifts' => $number('lifts'),
            'car_parks' => $number('car_parks'),
            'basements' => $number('basements'),
            'road_width_ft' => $number('road_width_ft'),
            'facing' => $text('facing'),
            'orientation' => $text('orientation'),
            'launch_date' => self::month($data['launch_date'] ?? null),
            'handover_date' => self::month($data['handover_date'] ?? null),
            'rajuk_approval_no' => $text('rajuk_approval_no'),
            'architect' => $text('architect'),
            'structural_engineer' => $text('structural_engineer'),
            'contractor' => $text('contractor'),
            'custom_facts' => self::customFacts($data['custom_facts'] ?? []),
            'overview' => BasicHtml::clean($data['overview'] ?? null),
            'design_narrative' => BasicHtml::clean($data['design_narrative'] ?? null),
            'highlights' => self::highlights($data['highlights'] ?? []),
            'cover_asset_id' => $data['cover_asset_id'] ?? null,
            'gallery' => self::gallery($data['gallery'] ?? []),
            'video_type' => $data['video_type'] ?? 'none',
            'video_url' => $text('video_url'),
            'video_asset_id' => $data['video_asset_id'] ?? null,
            'video_poster_asset_id' => $data['video_poster_asset_id'] ?? null,
            'video_captions_asset_id' => $data['video_captions_asset_id'] ?? null,
            'virtual_tour_url' => $text('virtual_tour_url'),
            'show_price' => (bool) ($data['show_price'] ?? false),
            'price_from_manual' => (bool) ($data['price_from_manual'] ?? false),
            'price_note' => $text('price_note'),
            'brochure_gated' => (bool) ($data['brochure_gated'] ?? true),
            'default_assignee_id' => $data['default_assignee_id'] ?? null,
            'whatsapp_override' => $text('whatsapp_override'),
        ];

        // The video fields that do not belong to the chosen type are dropped.
        $type = (string) $attributes['video_type'];

        if ($type !== 'youtube') {
            $attributes['video_url'] = null;
        }

        if ($type !== 'upload') {
            $attributes['video_asset_id'] = null;
            $attributes['video_captions_asset_id'] = null;
        }

        if ($type === 'none') {
            $attributes['video_poster_asset_id'] = null;
        }

        // `price_from_bdt` is derived from the unit types unless the editor set it by hand (land).
        if ($attributes['price_from_manual']) {
            $attributes['price_from_bdt'] = ($data['price_from_bdt'] ?? '') === '' ? null : (int) $data['price_from_bdt'];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function unit(array $row, int $position): array
    {
        return [
            'name' => trim((string) $row['name']),
            'size_sft' => (int) $row['size_sft'],
            'size_sft_max' => self::integer($row['size_sft_max'] ?? null),
            'bedrooms' => self::integer($row['bedrooms'] ?? null),
            'bathrooms' => self::integer($row['bathrooms'] ?? null),
            'balconies' => self::integer($row['balconies'] ?? null),
            'facing' => self::text($row['facing'] ?? null),
            'floor_range' => self::text($row['floor_range'] ?? null),
            'floor_plan_asset_id' => $row['floor_plan_asset_id'] ?? null,
            'plan_description' => self::text($row['plan_description'] ?? null),
            'plan_3d_asset_id' => $row['plan_3d_asset_id'] ?? null,
            'price_bdt' => self::integer($row['price_bdt'] ?? null),
            'show_price' => (bool) ($row['show_price'] ?? false),
            'availability' => $row['availability'] ?? 'available',
            'sort_order' => $position,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function progress(array $row): array
    {
        return [
            'reported_on' => self::month($row['reported_on']),
            'percent' => (int) $row['percent'],
            'stage' => $row['stage'],
            'note' => self::text($row['note'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    public static function nearby(array $place, int $position): array
    {
        return [
            'name' => trim((string) $place['name']),
            'category' => $place['category'],
            'minutes' => (int) $place['minutes'],
            'mode' => $place['mode'],
            'sort_order' => $position,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public static function document(array $document, int $position): array
    {
        return [
            'media_asset_id' => (int) $document['asset_id'],
            'title' => trim((string) $document['title']),
            'kind' => $document['kind'],
            'is_gated' => (bool) ($document['is_gated'] ?? false),
            'sort_order' => $position,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $facts
     * @return list<array{label: string, value: string}>|null
     */
    private static function customFacts(array $facts): ?array
    {
        $clean = array_values(array_map(
            fn (array $fact): array => ['label' => trim((string) $fact['label']), 'value' => trim((string) $fact['value'])],
            array_filter($facts, fn (array $fact): bool => trim((string) ($fact['label'] ?? '')) !== '' || trim((string) ($fact['value'] ?? '')) !== ''),
        ));

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{asset_id: int, title: string, text: string}>|null
     */
    private static function highlights(array $items): ?array
    {
        $clean = array_map(
            fn (array $item): array => [
                'asset_id' => (int) $item['asset_id'],
                'title' => trim((string) $item['title']),
                'text' => trim((string) ($item['text'] ?? '')),
            ],
            $items,
        );

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{asset_id: int, category: string, caption: string|null}>|null
     */
    private static function gallery(array $items): ?array
    {
        $clean = array_map(
            fn (array $item): array => [
                'asset_id' => (int) $item['asset_id'],
                'category' => (string) $item['category'],
                'caption' => self::text($item['caption'] ?? null),
            ],
            $items,
        );

        return $clean === [] ? null : $clean;
    }

    /** `YYYY-MM` from a month input → the first of that month (month precision, DM-PROJ). */
    private static function month(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value === '' ? null : Carbon::createFromFormat('!Y-m', substr($value, 0, 7))->startOfMonth();
    }

    private static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function integer(mixed $value): ?int
    {
        return ($value ?? '') === '' ? null : (int) $value;
    }
}
