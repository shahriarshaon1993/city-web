<?php

namespace Database\Seeders\Data;

use Illuminate\Support\Facades\File;

/**
 * The demo photos the development seeders import into the media library (outside production only).
 * A photo is a key `"{group}/{name}"` for a file in `storage/app/demo-src/{group}/` (git-ignored; the
 * owner drops the photos there). The seed data passes `image()` arrays around; the seeders turn them
 * back into keys with `keyOf()` and import the file through `ImportDemoMedia`.
 *
 * @phpstan-type MediaImage array{key: string, fallback: string}
 * @phpstan-type MediaVideo array{mp4: string, webm: string|null}
 * @phpstan-type MediaDocument array{url: string, bytes: int}
 */
final class SeedMedia
{
    public static function sourcePath(string $path = ''): string
    {
        return storage_path('app/demo-src'.($path === '' ? '' : '/'.$path));
    }

    /**
     * @return MediaImage
     */
    public static function image(string $key): array
    {
        return ['key' => $key, 'fallback' => $key];
    }

    /**
     * @param  array{fallback: string}  $image
     */
    public static function keyOf(array $image): ?string
    {
        return $image['fallback'];
    }

    /**
     * The image keys of a group, in file-name order ("progress/progress-01", …).
     *
     * @return list<string>
     */
    public static function keys(string $group): array
    {
        $files = File::glob(self::sourcePath($group).'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        $keys = array_map(fn (string $file): string => $group.'/'.pathinfo($file, PATHINFO_FILENAME), $files);
        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * Uploaded demo video, captions and the share crop are not seeded: the project video falls back to none.
     *
     * @return MediaVideo|null
     */
    public static function video(string $key): ?array
    {
        return null;
    }

    public static function captions(string $key): ?string
    {
        return null;
    }

    /**
     * @return MediaDocument
     */
    public static function document(string $key): array
    {
        return ['url' => '', 'bytes' => 0];
    }

    public static function ogImage(string $key): ?string
    {
        return null;
    }
}
