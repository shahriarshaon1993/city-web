<?php

namespace App\Support\Demo;

use Illuminate\Support\Facades\File;

/**
 * Demo images written by `php artisan demo:images`, served in the `MediaImage`
 * shape of resources/js/types/site.ts. The media resource of 11.1 must output
 * the same shape (DEC-31). Deleted with DemoContent in 16.4.
 *
 * @phpstan-type Rendition array{path: string, width: int}
 * @phpstan-type ManifestEntry array{
 *     dir: string,
 *     hash: string,
 *     alt: string,
 *     decorative: bool,
 *     caption: string|null,
 *     credit: string|null,
 *     width: int,
 *     height: int,
 *     focal: array{x: int, y: int},
 *     dominant_color: string|null,
 *     sources: array{avif: list<Rendition>, webp: list<Rendition>, jpeg: list<Rendition>},
 *     fallback: string,
 *     og: string
 * }
 * @phpstan-type MediaImage array{
 *     alt: string,
 *     decorative: bool,
 *     caption: string|null,
 *     credit: string|null,
 *     width: int,
 *     height: int,
 *     focal: array{x: int, y: int},
 *     dominant_color: string|null,
 *     sources: array{avif: string|null, webp: string, jpeg: string|null},
 *     fallback: string
 * }
 * @phpstan-type VideoEntry array{dir: string, mp4: string, webm: string|null, captions?: string|null}
 * @phpstan-type MediaVideo array{mp4: string, webm: string|null}
 * @phpstan-type DocumentEntry array{dir: string, path: string, bytes: int}
 * @phpstan-type MediaDocument array{url: string, bytes: int}
 */
final class DemoMedia
{
    /** Public URL prefix and folder under public/. */
    public const string PUBLIC_DIR = 'demo';

    /** @var array<string, ManifestEntry>|null */
    private static ?array $entries = null;

    /** @var array<string, VideoEntry>|null */
    private static ?array $videos = null;

    /** @var array<string, DocumentEntry>|null */
    private static ?array $documents = null;

    public static function sourcePath(string $path = ''): string
    {
        return storage_path('app/demo-src'.($path === '' ? '' : '/'.$path));
    }

    public static function outputPath(string $path = ''): string
    {
        return public_path(self::PUBLIC_DIR.($path === '' ? '' : '/'.$path));
    }

    public static function manifestPath(): string
    {
        return self::outputPath('manifest.json');
    }

    /**
     * The image under "{group}/{file name}", e.g. "hero/hero-01"; null when
     * `demo:images` has not produced it (the component then shows its placeholder).
     *
     * @return MediaImage|null
     */
    public static function image(string $key): ?array
    {
        $entry = self::entries()[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        return [
            'alt' => $entry['decorative'] ? '' : $entry['alt'],
            'decorative' => $entry['decorative'],
            'caption' => $entry['caption'],
            'credit' => $entry['credit'],
            'width' => $entry['width'],
            'height' => $entry['height'],
            'focal' => $entry['focal'],
            'dominant_color' => $entry['dominant_color'],
            'sources' => [
                'avif' => self::srcset($entry['sources']['avif']),
                'webp' => self::srcset($entry['sources']['webp']) ?? '',
                // A single JPEG is only the <img src> fallback.
                'jpeg' => count($entry['sources']['jpeg']) > 1 ? self::srcset($entry['sources']['jpeg']) : null,
            ],
            'fallback' => self::url($entry['fallback']),
        ];
    }

    /**
     * Absolute URL of the 1200×630 focal crop, for `seo.og.image`.
     */
    public static function ogImage(string $key): ?string
    {
        $entry = self::entries()[$key] ?? null;

        return $entry === null ? null : self::url($entry['og']);
    }

    /**
     * The hero video under "video/{file name}" (`MediaVideo` in types/site.ts,
     * HERO-020); null until a video is placed in storage/app/demo-src/video/.
     *
     * @return MediaVideo|null
     */
    public static function video(string $key): ?array
    {
        self::entries();
        $entry = self::$videos[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        return [
            'mp4' => self::url($entry['mp4']),
            'webm' => $entry['webm'] === null ? null : self::url($entry['webm']),
        ];
    }

    /**
     * URL of the WebVTT captions paired with a video ("video/{name}.vtt"), if any.
     */
    public static function captions(string $key): ?string
    {
        self::entries();
        $path = self::$videos[$key]['captions'] ?? null;

        return $path === null ? null : self::url($path);
    }

    /**
     * The PDF under "documents/{file name}" (DM-PDOC demo files), or null
     * until `demo:images --placeholders` has written it.
     *
     * @return MediaDocument|null
     */
    public static function document(string $key): ?array
    {
        self::entries();
        $entry = self::$documents[$key] ?? null;

        return $entry === null ? null : ['url' => self::url($entry['path']), 'bytes' => $entry['bytes']];
    }

    /**
     * Keys of all generated images, optionally of one group ("projects").
     *
     * @return list<string>
     */
    public static function keys(?string $group = null): array
    {
        $keys = array_keys(self::entries());

        if ($group !== null) {
            $keys = array_filter($keys, fn (string $key): bool => str_starts_with($key, $group.'/'));
        }

        return array_values($keys);
    }

    /**
     * @return array<string, ManifestEntry>
     */
    public static function entries(): array
    {
        if (self::$entries === null) {
            $path = self::manifestPath();
            $manifest = File::exists($path) ? json_decode(File::get($path), true) : null;

            /** @var array<string, ManifestEntry> $images */
            $images = is_array($manifest) ? ($manifest['images'] ?? []) : [];
            /** @var array<string, VideoEntry> $videos */
            $videos = is_array($manifest) ? ($manifest['videos'] ?? []) : [];
            /** @var array<string, DocumentEntry> $documents */
            $documents = is_array($manifest) ? ($manifest['documents'] ?? []) : [];
            self::$entries = $images;
            self::$videos = $videos;
            self::$documents = $documents;
        }

        return self::$entries;
    }

    /**
     * Forget the cached manifest (after `demo:images` rewrites it).
     */
    public static function flush(): void
    {
        self::$entries = null;
        self::$videos = null;
        self::$documents = null;
    }

    /**
     * @param  list<Rendition>  $renditions
     */
    private static function srcset(array $renditions): ?string
    {
        if ($renditions === []) {
            return null;
        }

        return implode(', ', array_map(
            fn (array $rendition): string => self::url($rendition['path']).' '.$rendition['width'].'w',
            $renditions,
        ));
    }

    private static function url(string $path): string
    {
        return asset(self::PUBLIC_DIR.'/'.$path);
    }
}
