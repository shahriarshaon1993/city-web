<?php

namespace App\Support\Seo;

use App\Support\Demo\DemoContent;
use App\Support\Format;

/**
 * PG-04 meta title and description from the Settings → SEO templates
 * (SEO-ONP-001/002), used when the project has no override. A part whose
 * value is missing is dropped with its words, so land and commercial
 * projects never read "– Bed" or "handover ." (owner, 4.2).
 *
 * @phpstan-type ProjectMetaInput array{name: string, segment: string, lifecycle_label: string, location: array{area: string, city: string}, size_min_sft: int|null, size_max_sft: int|null, beds_min: int|null, beds_max: int|null, land_katha: int|float|null, handover_date: string|null, lifecycle: string}
 */
final class ProjectMeta
{
    /** `{segment_label}` in titles: the noun buyers search for. */
    public const array SEGMENT_NOUNS = ['residential' => 'Apartments', 'commercial' => 'Commercial Spaces', 'mixed_use' => 'Mixed-use Spaces', 'land' => 'Land Plots'];

    /**
     * @param  ProjectMetaInput  $project
     */
    public static function title(array $project): string
    {
        $template = (string) DemoContent::settings('seo')['project_title_template'];
        $beds = self::range($project['beds_min'], $project['beds_max']);

        if ($beds === null) {
            $template = str_replace('{bedrooms_range} Bed ', '', $template);
        }

        return self::fill($template, $project, ['bedrooms_range' => $beds ?? '']);
    }

    /**
     * @param  ProjectMetaInput  $project
     */
    public static function description(array $project): string
    {
        $template = (string) DemoContent::settings('seo')['project_description_template'];
        $size = self::range($project['size_min_sft'], $project['size_max_sft']);

        if ($size === null) {
            $template = str_replace('{size_range} sft ', $project['land_katha'] === null ? '' : Format::katha($project['land_katha']).' ', $template);
        }

        $handover = $project['handover_date'] === null || $project['lifecycle'] === 'handed_over'
            ? null
            : Format::month($project['handover_date']);

        if ($handover === null) {
            $template = str_replace(', handover {handover}', '', $template);
        }

        return self::fill($template, $project, ['size_range' => $size ?? '', 'handover' => $handover ?? '']);
    }

    /**
     * @param  ProjectMetaInput  $project
     * @param  array<string, string>  $extra
     */
    private static function fill(string $template, array $project, array $extra): string
    {
        $noun = self::SEGMENT_NOUNS[$project['segment']] ?? '';
        $values = [
            'name' => $project['name'],
            'segment_label' => $noun,
            'segment_label_lower' => mb_strtolower($noun),
            'status_label' => $project['lifecycle_label'],
            'area' => $project['location']['area'],
            'city' => $project['location']['city'],
            ...$extra,
        ];

        $text = (string) preg_replace_callback('/\{(\w+)\}/', fn (array $match): string => $values[$match[1]] ?? '', $template);

        // Plain spaces: the head is read by crawlers and share previews.
        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)));
    }

    /**
     * "3–4", "4" or null; the unit is in the template.
     */
    private static function range(int|float|null $min, int|float|null $max): ?string
    {
        if ($min === null) {
            return null;
        }

        return $max === null || $max == $min
            ? Format::number($min)
            : Format::number($min)."\u{2013}".Format::number($max);
    }
}
