<?php

namespace App\Support\Redirects;

/**
 * Address handling shared by the redirect manager, the middleware and the 404 monitor
 * (SEO-TEC-002: lower case, no trailing slash, no `index.php`, no duplicate slashes).
 */
final class RedirectPath
{
    /** Paths the manager never redirects: they never reach it (the admin) or must keep working. */
    private const array PROTECTED = ['/admin', '/internal', '/up'];

    /**
     * The clean form of a request path; always starts with `/`.
     */
    public static function normalise(string $path): string
    {
        $path = rawurldecode($path);
        $path = preg_replace('#/{2,}#', '/', $path) ?? $path;
        $path = preg_replace('#(/index\.php)+$#i', '', $path) ?? $path;
        $path = '/'.trim($path, '/');

        return mb_strtolower($path);
    }

    /**
     * What a person typed as the "from" address: a path, `/old/*` for a prefix, or a full URL of
     * this site. Null when it is not an address of this site.
     *
     * @return array{source: string, is_prefix: bool}|null
     */
    public static function parseSource(string $input): ?array
    {
        $path = self::ownPath(trim($input));

        if ($path === null) {
            return null;
        }

        $isPrefix = str_ends_with($path, '/*');

        if ($isPrefix) {
            $path = substr($path, 0, -2);
        }

        if (str_contains($path, '*')) {
            return null;
        }

        return ['source' => self::normalise($path === '' ? '/' : $path), 'is_prefix' => $isPrefix];
    }

    /**
     * The path of an address that points into this site (a path, or a URL with this site's host),
     * without its query string or fragment; null for another host or something that is no address.
     */
    public static function ownPath(?string $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $address = trim($address);

        if (str_starts_with($address, '/') && ! str_starts_with($address, '//')) {
            return self::withoutQuery($address);
        }

        if (self::isAbsoluteUrl($address)) {
            $parts = parse_url($address);

            if (($parts['host'] ?? null) !== null && strcasecmp($parts['host'], self::host()) === 0) {
                return self::withoutQuery($parts['path'] ?? '/');
            }
        }

        return null;
    }

    public static function isAbsoluteUrl(string $address): bool
    {
        return preg_match('#^https?://[^\s/]+#i', $address) === 1;
    }

    /** Admin, internal endpoints and the health check can never be a source. */
    public static function isProtected(string $path): bool
    {
        foreach (self::PROTECTED as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private static function withoutQuery(string $address): string
    {
        return preg_split('/[?#]/', $address, 2)[0] ?? $address;
    }

    private static function host(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
