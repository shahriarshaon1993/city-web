<?php

namespace App\Support\Seo;

use App\Support\Demo\DemoContent;

/**
 * PG-06 meta title and description (SEO-ONP-001/002) and the indexability
 * rules of FR-LOC-003/004, used when the area has no override. The title
 * comes from the Settings → SEO location templates, the description is the
 * first 155 characters of the intro, cut at a word.
 */
final class LocationMeta
{
    /** FR-LOC-003: an area intro needs this many words to be indexable. */
    public const int MIN_INTRO_WORDS = 150;

    public const int DESCRIPTION_LENGTH = 155;

    public static function title(string $area, string $city, ?string $segment = null): string
    {
        $key = $segment === null ? 'location_title_template' : 'location_segment_title_template';
        $template = (string) DemoContent::settings('seo')[$key];

        return str_replace(['{area}', '{city}', '{segment_label}'], [$area, $city, (string) $segment], $template);
    }

    public static function description(string $introHtml): string
    {
        $text = self::text($introHtml);

        if (mb_strlen($text) <= self::DESCRIPTION_LENGTH) {
            return $text;
        }

        // Cut before the word that crosses the limit, leaving room for "…".
        $cut = mb_substr($text, 0, self::DESCRIPTION_LENGTH);
        $space = mb_strrpos($cut, ' ');

        return rtrim(mb_substr($cut, 0, $space === false ? self::DESCRIPTION_LENGTH - 1 : $space), ' ,;:.–-').'…';
    }

    /** FR-LOC-003: ≥ 1 published project and an intro of ≥ 150 words. */
    public static function areaIndexable(int $projectCount, string $introHtml): bool
    {
        return $projectCount >= 1 && self::words($introHtml) >= self::MIN_INTRO_WORDS;
    }

    public static function words(string $html): int
    {
        return str_word_count(self::text($html));
    }

    /** Plain text; block tags become spaces so paragraphs do not run together. */
    private static function text(string $html): string
    {
        $text = html_entity_decode(strip_tags((string) preg_replace('#</?(p|br|li|ul|ol|div|h[1-6]|blockquote)\b[^>]*>#i', ' $0', $html)), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
