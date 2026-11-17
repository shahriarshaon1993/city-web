<?php

namespace App\Support\Locations;

use App\Support\Html\BasicHtml;

/**
 * The editor payload of `SaveAreaRequest` turned into the columns of an area row. `SaveArea` writes
 * the result and `AreaHydrator` builds an unsaved copy from it (preview, revisions), so both agree.
 * The parent city and the sort order are the caller's business.
 */
final class AreaAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromPayload(array $data): array
    {
        return [
            'parent_id' => $data['parent_id'],
            'name' => trim($data['name']),
            'slug' => $data['slug'],
            'intro' => BasicHtml::clean($data['intro'] ?? null),
            'segment_intros' => self::segmentIntros($data['segment_intros'] ?? []),
            'facts' => self::facts($data['facts'] ?? []),
            'hero_asset_id' => $data['hero_asset_id'] ?? null,
            'latitude' => ($data['latitude'] ?? '') === '' ? null : (float) $data['latitude'],
            'longitude' => ($data['longitude'] ?? '') === '' ? null : (float) $data['longitude'],
            'zoom' => (int) $data['zoom'],
        ];
    }

    /**
     * @param  list<array{label: string, value: string}>  $facts
     * @return list<array{label: string, value: string}>|null
     */
    private static function facts(array $facts): ?array
    {
        $clean = array_map(
            fn (array $fact): array => ['label' => trim($fact['label']), 'value' => trim($fact['value'])],
            $facts,
        );

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  array<string, string|null>  $intros  segment value → intro
     * @return array<string, string>|null
     */
    private static function segmentIntros(array $intros): ?array
    {
        $clean = [];

        foreach ($intros as $segment => $intro) {
            $html = BasicHtml::clean($intro);

            if ($html !== null) {
                $clean[$segment] = $html;
            }
        }

        return $clean === [] ? null : $clean;
    }
}
