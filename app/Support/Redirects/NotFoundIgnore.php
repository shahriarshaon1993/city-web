<?php

namespace App\Support\Redirects;

use App\Support\Settings\Settings;

/**
 * SEO-TEC-009 ignore list: addresses matching one of these patterns are never written to the 404
 * monitor (bots probing for WordPress, missing icons and fonts). One regular expression per line,
 * matched case-insensitively against the path. Until someone saves a list, the defaults apply; a
 * saved empty list means "log everything".
 */
final class NotFoundIgnore
{
    /** @var list<string> */
    public const array DEFAULTS = [
        '\.(?:map|ico|png|jpe?g|gif|svg|webp|avif|css|js|woff2?|ttf|php|aspx?)$',
        '^/(?:wp-|wordpress|xmlrpc|\.env|\.git|phpmyadmin|cgi-bin)',
    ];

    /**
     * @return list<string>
     */
    public static function patterns(): array
    {
        $stored = Settings::stored('seo', 'not_found_ignore');

        return is_string($stored) ? self::split($stored) : self::DEFAULTS;
    }

    /** The list as the editor shows it: one pattern per line. */
    public static function text(): string
    {
        return implode("\n", self::patterns());
    }

    public static function isCustom(): bool
    {
        return is_string(Settings::stored('seo', 'not_found_ignore'));
    }

    public static function matches(string $path): bool
    {
        foreach (self::patterns() as $pattern) {
            if (@preg_match(self::delimit($pattern), $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /** True when the pattern compiles. */
    public static function isValid(string $pattern): bool
    {
        return @preg_match(self::delimit($pattern), '') !== false;
    }

    /**
     * @return list<string>
     */
    public static function split(string $text): array
    {
        $lines = array_map(trim(...), preg_split('/\R/', $text) ?: []);

        return array_values(array_unique(array_filter($lines, fn (string $line): bool => $line !== '')));
    }

    private static function delimit(string $pattern): string
    {
        return '~'.str_replace('~', '\~', $pattern).'~i';
    }
}
