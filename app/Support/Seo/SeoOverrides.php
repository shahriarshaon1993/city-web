<?php

namespace App\Support\Seo;

/**
 * DM-SEO on the public side (spec/cms.md §8.5): what an editor typed in the SEO panel wins over the
 * §9.3 templates. The stored array is the cached `seo` part of a page's data (`meta_title`,
 * `meta_description`, `robots`, `canonical_url`, `og_image`).
 */
final class SeoOverrides
{
    /**
     * @param  array<string, mixed>  $stored
     */
    public static function apply(array $stored, string $templateTitle, string $templateDescription, ?string $fallbackImage, ?string $imageAlt): SeoData
    {
        $seo = SeoData::raw(
            self::text($stored['meta_title'] ?? null) ?? $templateTitle,
            self::text($stored['meta_description'] ?? null) ?? $templateDescription,
        );

        $image = self::text($stored['og_image'] ?? null) ?? $fallbackImage;
        $seo->image($image === null || str_starts_with($image, 'http') ? $image : SeoData::absoluteUrl($image), $imageAlt);

        $robots = self::text($stored['robots'] ?? null);

        if ($robots !== null && $robots !== SeoData::ROBOTS_INDEX) {
            $seo->robots($robots);
        }

        $canonical = self::text($stored['canonical_url'] ?? null);

        if ($canonical !== null) {
            $seo->canonical($canonical);
        }

        return $seo;
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
