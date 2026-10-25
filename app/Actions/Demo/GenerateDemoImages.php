<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Converts every photo in storage/app/demo-src/{group}/ into public/demo/ and
 * writes the manifest DemoMedia reads (DEC-31). Unchanged photos are reused:
 * the folder name carries a hash of the file, its focal point and the rules
 * version, so URLs can be cached forever (CMS-MED-011).
 *
 * Optional per-group storage/app/demo-src/{group}/meta.json, keyed by file
 * name without extension: {"alt": "…", "focal": [x, y], "caption": "…",
 * "credit": "…", "decorative": false}. The `hero` group uses the HERO-011
 * profile; "{name}-mobile" files there are portrait mobile crops (HERO-013).
 * The `video` group holds hero videos, copied as they are (CopyDemoVideos).
 * SVG files (partner logos) are copied as they are too, without raster
 * conversions: `sources` stay empty and `fallback` is the SVG (DEC-47).
 *
 * @phpstan-import-type ManifestEntry from DemoMedia
 * @phpstan-import-type Rendition from DemoMedia
 */
final class GenerateDemoImages
{
    /** Bump after changing the conversion rules to re-encode everything. */
    private const int RULES_VERSION = 1;

    private const array EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'svg'];

    public function __construct(
        private readonly ConvertDemoImage $convert,
        private readonly CopyDemoVideos $copyVideos,
    ) {}

    /**
     * @param  (Closure(string, bool): void)|null  $progress  called per image with its key and whether it was (re)converted
     * @return array{converted: int, reused: int, removed: int, videos: int, avif: bool, warnings: list<string>}
     */
    public function handle(bool $force = false, ?Closure $progress = null): array
    {
        $sourceRoot = DemoMedia::sourcePath();

        if (! File::isDirectory($sourceRoot)) {
            throw new RuntimeException("No demo photos in {$sourceRoot}. Add photos there or run `php artisan demo:images --placeholders`.");
        }

        DemoMedia::flush();
        $previous = DemoMedia::entries();
        $withAvif = function_exists('imageavif');
        $entries = [];
        $warnings = [];
        $converted = 0;
        $reused = 0;

        foreach (File::directories($sourceRoot) as $groupDir) {
            $group = Str::slug(basename($groupDir));

            if ($group === CopyDemoVideos::GROUP) {
                continue;
            }

            $meta = $this->readMeta($groupDir, $warnings);

            foreach (File::files($groupDir) as $file) {
                if (! in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                    continue;
                }

                $name = $file->getFilenameWithoutExtension();
                $key = $group.'/'.Str::slug($name);

                if (isset($entries[$key])) {
                    $warnings[] = "{$key}: two files share this name; skipped {$file->getFilename()}.";

                    continue;
                }

                $info = $this->metadata($meta[$name] ?? [], $key, $warnings);
                $hash = $this->hash($file->getPathname(), $info['focal'], $withAvif);
                $dir = $key.'-'.$hash;

                $reusable = ! $force
                    && ($previous[$key]['dir'] ?? null) === $dir
                    && File::isDirectory(DemoMedia::outputPath($dir));

                $isSvg = strtolower($file->getExtension()) === 'svg';

                $renditions = match (true) {
                    $reusable => Arr::only($previous[$key], ['width', 'height', 'dominant_color', 'sources', 'fallback', 'og']),
                    $isSvg => $this->copySvg($file->getPathname(), DemoMedia::outputPath($dir), $dir),
                    default => $this->convert->handle($file->getPathname(), DemoMedia::outputPath($dir), $dir, $group === 'hero', $info['focal'], $withAvif),
                };

                if ($renditions === null) {
                    $warnings[] = "{$key}: the SVG has no viewBox; skipped.";

                    continue;
                }

                if (! $isSvg) {
                    $this->checkSize($key, $group, $renditions['width'], $renditions['height'], $warnings);
                }

                /** @var ManifestEntry $entry */
                $entry = ['dir' => $dir, 'hash' => $hash, ...$info, ...$renditions];
                $entries[$key] = $entry;

                $reusable ? $reused++ : $converted++;

                if ($progress !== null) {
                    $progress($key, ! $reusable);
                }
            }
        }

        $videos = $this->copyVideos->handle($warnings);
        $removed = $this->removeStale([...array_column($entries, 'dir'), ...array_column($videos, 'dir')]);

        File::put(DemoMedia::manifestPath(), (string) json_encode([
            'rules_version' => self::RULES_VERSION,
            'generated_at' => now()->toIso8601String(),
            'avif' => $withAvif,
            'images' => $entries,
            'videos' => $videos,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        DemoMedia::flush();

        return [
            'converted' => $converted,
            'reused' => $reused,
            'removed' => $removed,
            'videos' => count($videos),
            'avif' => $withAvif,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, array<string, mixed>>
     */
    private function readMeta(string $groupDir, array &$warnings): array
    {
        $path = $groupDir.'/meta.json';

        if (! File::exists($path)) {
            return [];
        }

        $meta = json_decode(File::get($path), true);

        if (! is_array($meta)) {
            $warnings[] = basename($groupDir).'/meta.json is not valid JSON; ignored.';

            return [];
        }

        /** @var array<string, array<string, mixed>> $meta */
        return $meta;
    }

    /**
     * CMS-MED-004 metadata with defaults.
     *
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $warnings
     * @return array{alt: string, decorative: bool, caption: string|null, credit: string|null, focal: array{x: int, y: int}}
     */
    private function metadata(array $raw, string $key, array &$warnings): array
    {
        $decorative = (bool) ($raw['decorative'] ?? false);
        $alt = is_string($raw['alt'] ?? null) ? trim($raw['alt']) : '';

        if (! $decorative && $alt === '') {
            $warnings[] = "{$key}: no alt text in meta.json; using the file name (CMS-MED-004).";
            $alt = Str::ucfirst(str_replace('-', ' ', Str::afterLast($key, '/')));
        }

        if (mb_strlen($alt) > 125) {
            $warnings[] = "{$key}: alt text is longer than 125 characters (HERO-014).";
        }

        $focal = is_array($raw['focal'] ?? null) ? array_values($raw['focal']) : [50, 50];

        return [
            'alt' => $alt,
            'decorative' => $decorative,
            'caption' => is_string($raw['caption'] ?? null) ? $raw['caption'] : null,
            'credit' => is_string($raw['credit'] ?? null) ? $raw['credit'] : null,
            'focal' => [
                'x' => $this->percent($focal[0] ?? 50),
                'y' => $this->percent($focal[1] ?? 50),
            ],
        ];
    }

    private function percent(mixed $value): int
    {
        return min(100, max(0, (int) round(is_numeric($value) ? (float) $value : 50)));
    }

    /**
     * @param  array{x: int, y: int}  $focal
     */
    private function hash(string $path, array $focal, bool $withAvif): string
    {
        $context = hash_init('xxh128');
        hash_update_file($context, $path);
        hash_update($context, $focal['x'].','.$focal['y'].'|'.self::RULES_VERSION.'|'.($withAvif ? 'avif' : ''));

        return substr(hash_final($context), 0, 8);
    }

    /**
     * Copies an SVG unchanged; its size is the viewBox (DEC-47).
     *
     * @return array{width: int, height: int, dominant_color: null, sources: array{avif: list<Rendition>, webp: list<Rendition>, jpeg: list<Rendition>}, fallback: string, og: string}|null
     */
    private function copySvg(string $sourcePath, string $outputDir, string $relativeDir): ?array
    {
        $viewBox = preg_match('/viewBox="\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)\s*"/', File::get($sourcePath), $match);

        if ($viewBox !== 1) {
            return null;
        }

        File::ensureDirectoryExists($outputDir);
        File::copy($sourcePath, $outputDir.'/original.svg');
        $path = $relativeDir.'/original.svg';

        return [
            'width' => (int) round((float) $match[1]),
            'height' => (int) round((float) $match[2]),
            'dominant_color' => null,
            'sources' => ['avif' => [], 'webp' => [], 'jpeg' => []],
            'fallback' => $path,
            'og' => $path,
        ];
    }

    /**
     * Upload minimums the admin will enforce later (HERO-010, HERO-013, FR-PROJ image rules).
     *
     * @param  list<string>  $warnings
     */
    private function checkSize(string $key, string $group, int $width, int $height, array &$warnings): void
    {
        [$minWidth, $minHeight, $rule] = match (true) {
            $group === 'hero' && str_ends_with($key, '-mobile') => [1080, 1350, 'HERO-013'],
            $group === 'hero' => [1920, 1080, 'HERO-010'],
            default => [1200, 0, 'gallery minimum'],
        };

        if ($width < $minWidth || $height < $minHeight) {
            $warnings[] = "{$key}: {$width}×{$height} is below {$minWidth}×{$minHeight} ({$rule}).";
        }
    }

    /**
     * Deletes output folders no manifest entry points to.
     *
     * @param  list<string>  $keep
     */
    private function removeStale(array $keep): int
    {
        $root = DemoMedia::outputPath();
        File::ensureDirectoryExists($root);
        $removed = 0;

        foreach (File::directories($root) as $groupDir) {
            foreach (File::directories($groupDir) as $imageDir) {
                if (! in_array(basename($groupDir).'/'.basename($imageDir), $keep, true)) {
                    File::deleteDirectory($imageDir);
                    $removed++;
                }
            }

            if (File::directories($groupDir) === [] && File::files($groupDir) === []) {
                File::deleteDirectory($groupDir);
            }
        }

        return $removed;
    }
}
