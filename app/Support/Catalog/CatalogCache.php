<?php

namespace App\Support\Catalog;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CMS-CACHE-002: versioned keys for the public catalogue (projects, areas, amenities, facet pages).
 * Every key embeds the `cachever:projects` counter (`projects:list:{hash}:v{n}`); any change to a
 * catalogue model raises the counter, so older entries are never read again and expire on their own.
 */
final class CatalogCache
{
    private const string VERSION_KEY = 'cachever:projects';

    /** CMS-CACHE-003: listing queries 10 minutes, project and area pages 24 hours. */
    public const int LISTING_TTL = 600;

    public const int PAGE_TTL = 86_400;

    /** Read once per request: with the database cache store every read is a query. */
    private static ?int $version = null;

    public static function version(): int
    {
        return self::$version ??= (int) Cache::get(self::VERSION_KEY, 1);
    }

    /** Forget the version read earlier in this process (a bust, or a test that changes it by hand). */
    public static function forgetVersion(): void
    {
        self::$version = null;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, int $seconds, Closure $callback): mixed
    {
        return Cache::remember($key.':v'.self::version(), $seconds, $callback);
    }

    /**
     * Raise the counter once the surrounding transaction has committed, so a reader that rebuilds the
     * cache in between never stores half-saved data under the new version.
     */
    public static function bust(): void
    {
        DB::afterCommit(static function (): void {
            Cache::add(self::VERSION_KEY, 1);
            Cache::increment(self::VERSION_KEY);
            self::forgetVersion();
        });
    }
}
