<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use GdImage;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Draws labelled placeholder photos into storage/app/demo-src/{group}/ until
 * the client's photos arrive (owner, 0.3). Existing files are never
 * overwritten; each image marks its focal point so crops can be checked.
 */
final class GenerateDemoPlaceholders
{
    /** Groups the demo content uses, with source sizes above the upload minimums. */
    private const array GROUPS = [
        'hero' => ['count' => 3, 'width' => 2560, 'height' => 1440],
        'projects' => ['count' => 8, 'width' => 2400, 'height' => 1600],
        'interiors' => ['count' => 4, 'width' => 2400, 'height' => 1600],
        'locations' => ['count' => 6, 'width' => 2000, 'height' => 1500],
        'news' => ['count' => 6, 'width' => 2000, 'height' => 1250],
        'team' => ['count' => 6, 'width' => 1200, 'height' => 1500],
        'progress' => ['count' => 4, 'width' => 2000, 'height' => 1500],
    ];

    /** HERO-013 portrait crop, one per hero image. */
    private const array MOBILE = ['width' => 1080, 'height' => 1350, 'focal' => [50, 45]];

    private const array FOCALS = [[50, 50], [35, 45], [65, 40], [50, 62], [30, 55], [70, 50]];

    private const array PORTRAIT_FOCAL = [50, 35];

    /** Sky top, sky bottom, buildings, windows — brand scales (design/foundations.md §2.2). */
    private const array PALETTES = [
        ['#01343c', '#ffcec2', '#001f24', '#fea794'],
        ['#91ccd7', '#eff9fb', '#46413f', '#f7f2f0'],
        ['#001f24', '#3392a1', '#01464f', '#fea794'],
        ['#fea794', '#fef9f7', '#5c5755', '#ffe7e1'],
    ];

    /**
     * @return list<string> files written, relative to storage/app/demo-src
     */
    public function handle(): array
    {
        $written = [];

        foreach (self::GROUPS as $group => $spec) {
            $dir = DemoMedia::sourcePath($group);
            File::ensureDirectoryExists($dir);

            $metaPath = $dir.'/meta.json';
            $existing = File::exists($metaPath) ? json_decode(File::get($metaPath), true) : [];
            /** @var array<string, array<string, mixed>> $meta */
            $meta = is_array($existing) ? $existing : [];

            for ($index = 1; $index <= $spec['count']; $index++) {
                $name = sprintf('%s-%02d', $group, $index);
                $focal = $group === 'team' ? self::PORTRAIT_FOCAL : self::FOCALS[($index - 1) % count(self::FOCALS)];
                $images = [[$name, $spec['width'], $spec['height'], $focal, "{$group} {$index}"]];

                if ($group === 'hero') {
                    $images[] = [$name.'-mobile', self::MOBILE['width'], self::MOBILE['height'], self::MOBILE['focal'], "{$group} {$index} mobile"];
                }

                foreach ($images as [$file, $width, $height, $point, $label]) {
                    if (File::glob($dir.'/'.$file.'.*') === []) {
                        imagejpeg($this->draw($width, $height, $point, $index, $label), "{$dir}/{$file}.jpg", 90);
                        $written[] = "{$group}/{$file}.jpg";
                    }

                    $meta[$file] ??= [
                        'alt' => 'Placeholder photo: '.$label,
                        'focal' => $point,
                        'caption' => null,
                        'credit' => 'Placeholder image',
                        'decorative' => false,
                    ];
                }
            }

            File::put($metaPath, (string) json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return $written;
    }

    /**
     * @param  array{0: int, 1: int}  $focal
     */
    private function draw(int $width, int $height, array $focal, int $index, string $label): GdImage
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));

        if ($image === false) {
            throw new RuntimeException('GD could not allocate an image.');
        }

        [$skyTop, $skyBottom, $building, $window] = array_map(
            $this->rgb(...),
            self::PALETTES[($index - 1) % count(self::PALETTES)],
        );

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max(1, $height - 1);
            imageline($image, 0, $y, $width, $y, $this->allocate($image, [
                $this->channel($skyTop[0] + ($skyBottom[0] - $skyTop[0]) * $ratio),
                $this->channel($skyTop[1] + ($skyBottom[1] - $skyTop[1]) * $ratio),
                $this->channel($skyTop[2] + ($skyBottom[2] - $skyTop[2]) * $ratio),
            ]));
        }

        $this->skyline($image, $building, $window, crc32($label));
        $this->focalMarker($image, $focal);
        $this->label($image, ['PLACEHOLDER', strtoupper($label), "{$width} x {$height}  FOCAL {$focal[0]},{$focal[1]}"]);

        return $image;
    }

    /**
     * @param  array{int<0, 255>, int<0, 255>, int<0, 255>}  $building
     * @param  array{int<0, 255>, int<0, 255>, int<0, 255>}  $window
     */
    private function skyline(GdImage $image, array $building, array $window, int $seed): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $buildingColor = $this->allocate($image, $building);
        $windowColor = $this->allocate($image, $window);
        $cell = max(12, intdiv(min($width, $height), 40));

        mt_srand($seed);
        $x = 0;

        while ($x < $width) {
            $towerWidth = mt_rand((int) ($width * 0.06), (int) ($width * 0.14));
            $towerHeight = mt_rand((int) ($height * 0.25), (int) ($height * 0.6));
            $top = $height - $towerHeight;
            imagefilledrectangle($image, $x, $top, $x + $towerWidth, $height, $buildingColor);

            for ($wy = $top + $cell; $wy < $height - $cell; $wy += $cell * 2) {
                for ($wx = $x + $cell; $wx < $x + $towerWidth - $cell; $wx += $cell * 2) {
                    if (mt_rand(0, 2) > 0) {
                        imagefilledrectangle($image, $wx, $wy, $wx + $cell - 1, $wy + $cell - 1, $windowColor);
                    }
                }
            }

            $x += $towerWidth + mt_rand(0, (int) ($width * 0.015));
        }

        mt_srand();
    }

    /**
     * @param  array{0: int, 1: int}  $focal
     */
    private function focalMarker(GdImage $image, array $focal): void
    {
        $x = (int) round(imagesx($image) * $focal[0] / 100);
        $y = (int) round(imagesy($image) * $focal[1] / 100);
        $radius = (int) round(min(imagesx($image), imagesy($image)) * 0.05);
        $white = (int) imagecolorallocate($image, 255, 255, 255);

        $thickness = max(2, intdiv($radius, 8));

        // imageellipse() ignores the thickness setting.
        for ($ring = 0; $ring < $thickness; $ring++) {
            imageellipse($image, $x, $y, ($radius + $ring) * 2, ($radius + $ring) * 2, $white);
        }

        imagesetthickness($image, $thickness);
        imageline($image, $x - $radius * 2, $y, $x + $radius * 2, $y, $white);
        imageline($image, $x, $y - $radius * 2, $x, $y + $radius * 2, $white);
        imagesetthickness($image, 1);
    }

    /**
     * GD's built-in font scaled up, so no TTF file is needed on the host.
     *
     * @param  non-empty-list<string>  $lines
     */
    private function label(GdImage $image, array $lines): void
    {
        $font = 5;
        $charWidth = imagefontwidth($font);
        $lineHeight = imagefontheight($font) + 3;
        $textWidth = max(1, max(array_map('strlen', $lines)) * $charWidth);
        $textHeight = max(1, count($lines) * $lineHeight);
        $scale = max(2, intdiv(imagesx($image), 480));

        $text = imagecreatetruecolor($textWidth, $textHeight);

        if ($text === false) {
            return;
        }

        imagealphablending($text, false);
        imagefill($text, 0, 0, (int) imagecolorallocatealpha($text, 0, 0, 0, 127));
        imagesavealpha($text, true);

        foreach ($lines as $line => $value) {
            imagestring($text, $font, 0, $line * $lineHeight, $value, (int) imagecolorallocate($text, 255, 255, 255));
        }

        $padding = 6 * $scale;
        imagealphablending($image, true);
        imagefilledrectangle(
            $image,
            $padding,
            $padding,
            $padding * 3 + $textWidth * $scale,
            $padding * 3 + $textHeight * $scale,
            (int) imagecolorallocatealpha($image, 0, 0, 0, 70),
        );
        imagecopyresized($image, $text, $padding * 2, $padding * 2, 0, 0, $textWidth * $scale, $textHeight * $scale, $textWidth, $textHeight);
    }

    /**
     * @return array{int<0, 255>, int<0, 255>, int<0, 255>}
     */
    private function rgb(string $hex): array
    {
        return [
            $this->channel(hexdec(substr($hex, 1, 2))),
            $this->channel(hexdec(substr($hex, 3, 2))),
            $this->channel(hexdec(substr($hex, 5, 2))),
        ];
    }

    /**
     * @return int<0, 255>
     */
    private function channel(int|float $value): int
    {
        return min(255, max(0, (int) round($value)));
    }

    /**
     * @param  array{int<0, 255>, int<0, 255>, int<0, 255>}  $rgb
     */
    private function allocate(GdImage $image, array $rgb): int
    {
        return (int) imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }
}
