<?php

namespace App\Support\Redirects;

use App\Support\Settings\Settings;

/**
 * SEO-TEC-010: the robots.txt body. Edited under SEO → Robots.txt and stored as `seo.robots_body`;
 * until someone saves one, the default body applies. Anything that is not production always serves
 * `Disallow: /`, whatever is stored.
 */
final class RobotsTxt
{
    public static function defaultBody(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /search',
            'Disallow: /shortlist',
            'Disallow: /compare',
            'Disallow: /thank-you/',
            'Disallow: /preview/',
            'Disallow: /internal/',
            '',
            'Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml',
        ])."\n";
    }

    /** What is saved, or the default. */
    public static function body(): string
    {
        return self::isCustom() ? (string) Settings::stored('seo', 'robots_body') : self::defaultBody();
    }

    public static function isCustom(): bool
    {
        $stored = Settings::stored('seo', 'robots_body');

        return is_string($stored) && trim($stored) !== '';
    }

    public static function blocksEverything(): bool
    {
        return ! app()->isProduction();
    }

    /** What `/robots.txt` answers right now on this environment. */
    public static function served(): string
    {
        return self::blocksEverything() ? "User-agent: *\nDisallow: /\n" : self::body();
    }
}
