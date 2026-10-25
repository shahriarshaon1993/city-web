<?php

namespace App\Actions\Demo;

use GdImage;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Writes the conversions of one demo photo with PHP GD (DEC-31): the hero
 * profile of HERO-011 or the media profile of CMS-MED-005, plus the `og`
 * focal crop and the dominant colour of `thumb`.
 *
 * @phpstan-import-type Rendition from \App\Support\Demo\DemoMedia
 */
final class ConvertDemoImage
{
    /** HERO-011: every width in AVIF, WebP and JPEG. */
    public const array HERO_WIDTHS = [640, 960, 1280, 1600, 1920, 2560];

    /** CMS-MED-005: AVIF + WebP each, JPEG for `lg` only; widths above the source are skipped (`xl` only from 2560). */
    public const array MEDIA_PROFILE = ['thumb' => 400, 'card' => 800, 'md' => 1280, 'lg' => 1920, 'xl' => 2560];

    private const int AVIF_QUALITY = 50;

    private const int WEBP_QUALITY = 72;

    private const int JPEG_QUALITY = 78;

    private const int OG_WIDTH = 1200;

    private const int OG_HEIGHT = 630;

    /**
     * @param  string  $outputDir  absolute folder for this image's files
     * @param  string  $relativeDir  the same folder relative to public/demo
     * @param  array{x: int, y: int}  $focal  percent
     * @return array{
     *     width: int,
     *     height: int,
     *     dominant_color: string,
     *     sources: array{avif: list<Rendition>, webp: list<Rendition>, jpeg: list<Rendition>},
     *     fallback: string,
     *     og: string
     * }
     */
    public function handle(string $sourcePath, string $outputDir, string $relativeDir, bool $heroProfile, array $focal, bool $withAvif): array
    {
        $source = $this->load($sourcePath);
        $width = imagesx($source);
        $height = imagesy($source);

        File::ensureDirectoryExists($outputDir);

        $sources = ['avif' => [], 'webp' => [], 'jpeg' => []];
        $fallback = null;

        foreach ($this->plan($width, $heroProfile) as ['name' => $name, 'width' => $targetWidth, 'jpeg' => $withJpeg]) {
            $image = $this->resize($source, $targetWidth);

            if ($withAvif) {
                imageavif($image, "{$outputDir}/{$name}.avif", self::AVIF_QUALITY);
                $sources['avif'][] = ['path' => "{$relativeDir}/{$name}.avif", 'width' => $targetWidth];
            }

            imagewebp($image, "{$outputDir}/{$name}.webp", self::WEBP_QUALITY);
            $sources['webp'][] = ['path' => "{$relativeDir}/{$name}.webp", 'width' => $targetWidth];

            if ($withJpeg) {
                imageinterlace($image, true);
                imagejpeg($image, "{$outputDir}/{$name}.jpg", self::JPEG_QUALITY);
                $sources['jpeg'][] = ['path' => "{$relativeDir}/{$name}.jpg", 'width' => $targetWidth];

                // <img src>: the largest JPEG up to 1920 px (the `lg` conversion).
                if ($fallback === null || $targetWidth <= self::MEDIA_PROFILE['lg']) {
                    $fallback = "{$relativeDir}/{$name}.jpg";
                }
            }
        }

        $og = $this->ogCrop($source, $focal);
        imageinterlace($og, true);
        imagejpeg($og, "{$outputDir}/og.jpg", self::JPEG_QUALITY);

        return [
            'width' => $width,
            'height' => $height,
            'dominant_color' => $this->dominantColor($source),
            'sources' => $sources,
            'fallback' => (string) $fallback,
            'og' => "{$relativeDir}/og.jpg",
        ];
    }

    /**
     * @return list<array{name: string, width: int, jpeg: bool}>
     */
    private function plan(int $sourceWidth, bool $heroProfile): array
    {
        if ($heroProfile) {
            $widths = array_values(array_filter(self::HERO_WIDTHS, fn (int $width): bool => $width <= $sourceWidth));

            return array_map(
                fn (int $width): array => ['name' => "w{$width}", 'width' => $width, 'jpeg' => true],
                $widths === [] ? [$sourceWidth] : $widths,
            );
        }

        $profile = array_filter(self::MEDIA_PROFILE, fn (int $width): bool => $width <= $sourceWidth);

        if ($profile === []) {
            return [['name' => 'thumb', 'width' => $sourceWidth, 'jpeg' => true]];
        }

        $jpegName = isset($profile['lg']) ? 'lg' : array_key_last($profile);

        $plan = [];

        foreach ($profile as $name => $width) {
            $plan[] = ['name' => $name, 'width' => $width, 'jpeg' => $name === $jpegName];
        }

        return $plan;
    }

    private function load(string $path): GdImage
    {
        $image = imagecreatefromstring(File::get($path));

        if ($image === false) {
            throw new RuntimeException("GD cannot read {$path}.");
        }

        // Camera photos store their rotation in EXIF instead of the pixels.
        if (function_exists('exif_read_data') && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true)) {
            $exif = @exif_read_data($path);
            $angle = match (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                $image = $rotated === false ? $image : $rotated;
            }
        }

        return $image;
    }

    private function resize(GdImage $source, int $width): GdImage
    {
        $height = max(1, (int) round(imagesy($source) * $width / imagesx($source)));

        return $this->resample($source, $width, $height, 0, 0, imagesx($source), imagesy($source));
    }

    /**
     * 1200×630 crop centred on the focal point (CMS-MED-005 `og`).
     *
     * @param  array{x: int, y: int}  $focal
     */
    private function ogCrop(GdImage $source, array $focal): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = max(self::OG_WIDTH / $width, self::OG_HEIGHT / $height);
        $cropWidth = (int) round(self::OG_WIDTH / $scale);
        $cropHeight = (int) round(self::OG_HEIGHT / $scale);
        $x = (int) round(min(max($focal['x'] / 100 * $width - $cropWidth / 2, 0), $width - $cropWidth));
        $y = (int) round(min(max($focal['y'] / 100 * $height - $cropHeight / 2, 0), $height - $cropHeight));

        return $this->resample($source, self::OG_WIDTH, self::OG_HEIGHT, $x, $y, $cropWidth, $cropHeight);
    }

    /**
     * Average colour of the `thumb` conversion (design/foundations.md §5.4).
     */
    private function dominantColor(GdImage $source): string
    {
        $thumb = $this->resize($source, min(self::MEDIA_PROFILE['thumb'], imagesx($source)));
        $pixel = $this->resample($thumb, 1, 1, 0, 0, imagesx($thumb), imagesy($thumb));
        $rgb = imagecolorat($pixel, 0, 0);

        return sprintf('#%02x%02x%02x', ($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
    }

    private function resample(GdImage $source, int $width, int $height, int $sourceX, int $sourceY, int $sourceWidth, int $sourceHeight): GdImage
    {
        $target = imagecreatetruecolor(max(1, $width), max(1, $height));

        if ($target === false) {
            throw new RuntimeException('GD could not allocate an image.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, $width, $height, $sourceWidth, $sourceHeight);

        return $target;
    }
}
