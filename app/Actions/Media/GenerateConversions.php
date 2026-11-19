<?php

namespace App\Actions\Media;

use App\Models\MediaAsset;
use App\Support\Media\MediaKind;
use App\Support\Media\Renditions;
use GdImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * CMS-MED-005 / HERO-011 / CMS-MED-006: writes the conversions of one image next to its original
 * (`media/{id}/{hash}-w{width}.{avif|webp|jpg}`, `{hash}-og.jpg`), decoding the source once with
 * PHP GD, and stores the result as the `renditions` custom property in the `MediaImage` shape of resources/js/types/site.ts
 * (DEC-31), so the media resource and the demo manifest feed `ResponsiveImage` identically.
 *
 * - widths 400 / 800 / 1280 / 1920 / 2560 (a width above the source is skipped), AVIF (when GD has
 *   it) + WebP each, and a JPEG at 1920 (or the largest available width) as `<img src>` fallback;
 * - a source of at least 1920 × 1080 is hero-eligible: it also gets 640 / 960 / 1600 and JPEG at
 *   every hero width, because HERO-011 asks for all six widths in all three formats;
 * - `og`: 1200 × 630 crop around the focal point; `dominant_color`: average colour of `thumb`.
 *
 * Idempotent: running it again replaces the files and drops the ones that are no longer wanted
 * (used after a focal point change and by `media-library:regenerate`-style repairs).
 */
final class GenerateConversions
{
    /** CMS-MED-005 `thumb`, `card`, `md`, `lg`, `xl`. */
    public const array LIBRARY_WIDTHS = [400, 800, 1280, 1920, 2560];

    /** HERO-011 widths that the library set does not already have. */
    public const array HERO_EXTRA_WIDTHS = [640, 960, 1600];

    public const array HERO_WIDTHS = [640, 960, 1280, 1600, 1920, 2560];

    public const int FALLBACK_MAX_WIDTH = 1920;

    private const int AVIF_QUALITY = 50;

    private const int AVIF_SPEED = 6;

    private const int WEBP_QUALITY = 72;

    private const int JPEG_QUALITY = 78;

    private const int OG_WIDTH = 1200;

    private const int OG_HEIGHT = 630;

    private const int MEMORY_LIMIT_MB = 512;

    public function handle(Media $media): Renditions
    {
        $asset = $media->model;

        if (! $asset instanceof MediaAsset || $asset->kind !== MediaKind::Image) {
            throw new RuntimeException('Only images have conversions.');
        }

        $this->raiseMemoryLimit();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);
        $workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'media-conversions-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($workDir);

        try {
            $sourcePath = $workDir.DIRECTORY_SEPARATOR.'source';
            $this->download($disk, $media->getPathRelativeToRoot(), $sourcePath);

            $source = $this->load($sourcePath, $media->mime_type);
            $width = imagesx($source);
            $height = imagesy($source);

            $directory = dirname($media->getPathRelativeToRoot());
            $hash = pathinfo($media->file_name, PATHINFO_FILENAME);
            $withAvif = (gd_info()['AVIF Support'] ?? false) === true;

            $sources = ['avif' => [], 'webp' => [], 'jpeg' => []];
            $fallback = null;
            $written = [];

            foreach ($this->plan($width, $height) as ['width' => $targetWidth, 'jpeg' => $withJpeg]) {
                $image = $this->resize($source, $targetWidth);
                $base = "{$hash}-w{$targetWidth}";

                if ($withAvif) {
                    $path = "{$directory}/{$base}.avif";
                    $this->put($disk, $workDir, $path, fn (string $file): bool => imageavif($image, $file, self::AVIF_QUALITY, self::AVIF_SPEED));
                    $sources['avif'][] = ['path' => $path, 'width' => $targetWidth];
                    $written[] = $path;
                }

                $path = "{$directory}/{$base}.webp";
                $this->put($disk, $workDir, $path, fn (string $file): bool => imagewebp($image, $file, self::WEBP_QUALITY));
                $sources['webp'][] = ['path' => $path, 'width' => $targetWidth];
                $written[] = $path;

                if ($withJpeg) {
                    $path = "{$directory}/{$base}.jpg";
                    $this->put($disk, $workDir, $path, fn (string $file): bool => $this->saveJpeg($image, $file));
                    $sources['jpeg'][] = ['path' => $path, 'width' => $targetWidth];
                    $written[] = $path;

                    // <img src>: the largest JPEG up to 1920 px.
                    if ($fallback === null || $targetWidth <= self::FALLBACK_MAX_WIDTH) {
                        $fallback = $path;
                    }
                }
            }

            $og = "{$directory}/{$hash}-og.jpg";
            $ogImage = $this->ogCrop($source, ['x' => $asset->focal_x, 'y' => $asset->focal_y]);
            $this->put($disk, $workDir, $og, fn (string $file): bool => $this->saveJpeg($ogImage, $file));
            $written[] = $og;

            $renditions = new Renditions($sources['avif'], $sources['webp'], $sources['jpeg'], (string) $fallback, $og);

            $this->removeStale($disk, $media, $written);

            $media->setCustomProperty('renditions', $renditions->toArray())->save();
            $asset->forceFill([
                'width' => $width,
                'height' => $height,
                'dominant_color' => $this->dominantColor($source),
                'conversions_status' => MediaAsset::STATUS_READY,
            ])->save();

            return $renditions;
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * @return list<array{width: int, jpeg: bool}>
     */
    private function plan(int $sourceWidth, int $sourceHeight): array
    {
        $widths = array_values(array_filter(self::LIBRARY_WIDTHS, fn (int $width): bool => $width <= $sourceWidth));
        $heroEligible = $sourceWidth >= 1920 && $sourceHeight >= 1080;

        if ($heroEligible) {
            $widths = array_values(array_unique([...$widths, ...self::HERO_EXTRA_WIDTHS]));
            sort($widths);
        }

        if ($widths === []) {
            $widths = [$sourceWidth];
        }

        $single = in_array(self::FALLBACK_MAX_WIDTH, $widths, true) ? self::FALLBACK_MAX_WIDTH : max($widths);

        return array_map(fn (int $width): array => [
            'width' => $width,
            'jpeg' => $heroEligible ? in_array($width, self::HERO_WIDTHS, true) : $width === $single,
        ], $widths);
    }

    private function raiseMemoryLimit(): void
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return;
        }

        $bytes = (int) $limit * match (strtoupper(substr($limit, -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        if ($bytes < self::MEMORY_LIMIT_MB * 1024 ** 2) {
            ini_set('memory_limit', self::MEMORY_LIMIT_MB.'M');
        }
    }

    private function download(FilesystemAdapter $disk, string $path, string $target): void
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException("The original {$path} cannot be read.");
        }

        $out = fopen($target, 'wb');

        if ($out === false) {
            throw new RuntimeException('The working folder is not writable.');
        }

        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
    }

    /**
     * @param  callable(string): bool  $write  writes the encoded image to the given local file
     */
    private function put(FilesystemAdapter $disk, string $workDir, string $path, callable $write): void
    {
        $local = $workDir.DIRECTORY_SEPARATOR.basename($path);

        if (! $write($local)) {
            throw new RuntimeException("Could not encode {$path}.");
        }

        $handle = fopen($local, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Could not read back {$path}.");
        }

        $disk->put($path, $handle);
        fclose($handle);

        File::delete($local);
    }

    /**
     * Files a previous run wrote that this run does not (a width that no longer applies).
     *
     * @param  list<string>  $written
     */
    private function removeStale(FilesystemAdapter $disk, Media $media, array $written): void
    {
        $old = Renditions::fromStored($media->getCustomProperty('renditions'));

        if ($old === null) {
            return;
        }

        $stale = array_values(array_diff($old->paths(), $written));

        if ($stale !== []) {
            $disk->delete($stale);
        }
    }

    private function load(string $path, ?string $mime): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            throw new RuntimeException('GD cannot read the image.');
        }

        // Camera photos store their rotation in EXIF instead of the pixels.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $angle = match (is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1) {
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
     * JPEG has no transparency: PNG and WebP sources are laid on white first.
     */
    private function saveJpeg(GdImage $image, string $file): bool
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));

        if ($flat === false) {
            throw new RuntimeException('GD could not allocate an image.');
        }

        imagefill($flat, 0, 0, (int) imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imageinterlace($flat, true);

        return imagejpeg($flat, $file, self::JPEG_QUALITY);
    }

    /**
     * 1200 × 630 crop centred on the focal point (CMS-MED-005 `og`).
     *
     * @param  array{x: int, y: int}  $focal  percent
     */
    private function ogCrop(GdImage $source, array $focal): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = max(self::OG_WIDTH / $width, self::OG_HEIGHT / $height);
        $cropWidth = min($width, (int) round(self::OG_WIDTH / $scale));
        $cropHeight = min($height, (int) round(self::OG_HEIGHT / $scale));
        $x = (int) round(min(max($focal['x'] / 100 * $width - $cropWidth / 2, 0), $width - $cropWidth));
        $y = (int) round(min(max($focal['y'] / 100 * $height - $cropHeight / 2, 0), $height - $cropHeight));

        return $this->resample($source, self::OG_WIDTH, self::OG_HEIGHT, $x, $y, $cropWidth, $cropHeight);
    }

    /**
     * Average colour of the `thumb` conversion (design/foundations.md §5.4).
     */
    private function dominantColor(GdImage $source): string
    {
        $thumb = $this->resize($source, min(self::LIBRARY_WIDTHS[0], imagesx($source)));
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
