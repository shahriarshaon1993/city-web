<?php

namespace App\Support\Pages;

/**
 * Slugs a custom page (PG-25) can never use: the spec list of
 * spec/pages.md §5.2 plus the application's own top-level paths (auth,
 * settings, the local styleguide). The catch-all route excludes them, and
 * the page builder's slug rule (16.2) must reject them.
 */
final class ReservedSlugs
{
    /** @var list<string> */
    public const array SPEC = [
        'projects', 'locations', 'about', 'services', 'landowner', 'nrb', 'news', 'careers',
        'contact', 'faq', 'search', 'shortlist', 'compare', 'thank-you', 'privacy-policy',
        'terms', 'admin', 'login', 'api', 'internal', 'storage', 'build', 'sitemap',
        'sitemaps', 'robots.txt', 'up',
    ];

    /** @var list<string> */
    public const array APP = [
        'dashboard', 'settings', 'styleguide', 'register', 'logout', 'forgot-password',
        'reset-password', 'two-factor-challenge', 'confirm-password', 'email', 'user',
        'demo', 'vendor', 'index.php', 'favicon.ico', 'newsletter',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [...self::SPEC, ...self::APP];
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::all(), true);
    }

    /**
     * Route constraint for one slug segment: lower-case words joined by
     * hyphens, none of them reserved.
     */
    public static function routePattern(): string
    {
        $reserved = implode('|', array_map(preg_quote(...), self::all()));

        return '(?!(?:'.$reserved.')$)[a-z0-9]+(?:-[a-z0-9]+)*';
    }
}
