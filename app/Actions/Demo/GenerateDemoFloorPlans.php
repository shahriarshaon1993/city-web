<?php

namespace App\Actions\Demo;

use App\Support\Demo\DemoMedia;
use Illuminate\Support\Facades\File;

/**
 * Writes fictional line-drawing floor plans (SVG) into
 * storage/app/demo-src/plans/ for the demo unit types (owner, 4.2), until the
 * client's plans arrive. Existing files are never overwritten. The sheet is
 * white so a plan reads on the dark lightbox too.
 *
 * @phpstan-type Room array{0: string, 1: int, 2: int, 3: int, 4: int, 5?: string}
 */
final class GenerateDemoFloorPlans
{
    /** Grid units → SVG px. */
    private const int SCALE = 10;

    private const int MARGIN = 40;

    /** Rooms: [label, x, y, width, height, dimensions?] on a 100-unit-wide grid. */
    private const array PLANS = [
        'plans-01' => [
            'alt' => 'Placeholder floor plan of a two-bedroom apartment',
            'height' => 70,
            'mirror' => false,
            'rooms' => [
                ['Bedroom 1', 0, 0, 35, 32, "12'0\" × 11'0\""],
                ['Bath', 35, 0, 15, 16],
                ['Bath', 35, 16, 15, 16],
                ['Bedroom 2', 50, 0, 30, 32, "11'0\" × 10'6\""],
                ['Balcony', 80, 0, 20, 32],
                ['Living · Dining', 0, 32, 55, 38, "19'0\" × 13'0\""],
                ['Kitchen', 55, 32, 25, 20, "9'0\" × 7'6\""],
                ['Utility', 55, 52, 25, 18],
                ['Foyer', 80, 32, 20, 38],
            ],
        ],
        'plans-02' => [
            'alt' => 'Placeholder floor plan of a three-bedroom apartment',
            'height' => 70,
            'mirror' => false,
            'rooms' => self::THREE_BED,
        ],
        'plans-03' => [
            'alt' => 'Placeholder floor plan of a three-bedroom apartment, mirrored layout',
            'height' => 70,
            'mirror' => true,
            'rooms' => self::THREE_BED,
        ],
        'plans-04' => [
            'alt' => 'Placeholder floor plan of a four-bedroom apartment',
            'height' => 75,
            'mirror' => false,
            'rooms' => [
                ['Bedroom 1', 0, 0, 30, 32, "15'0\" × 13'0\""],
                ['Dress', 30, 0, 12, 16],
                ['Bath', 30, 16, 12, 16],
                ['Bedroom 2', 42, 0, 24, 32, "12'6\" × 11'6\""],
                ['Bath', 66, 0, 12, 16],
                ['Bath', 66, 16, 12, 16],
                ['Bedroom 3', 78, 0, 22, 32, "12'0\" × 11'0\""],
                ['Balcony', 0, 32, 10, 43],
                ['Family living', 10, 32, 36, 43, "22'0\" × 15'0\""],
                ['Dining', 46, 32, 26, 24, "14'0\" × 11'0\""],
                ['Bedroom 4', 72, 32, 28, 24, "12'0\" × 10'6\""],
                ['Kitchen', 46, 56, 20, 19, "10'6\" × 8'0\""],
                ['Utility', 66, 56, 12, 19],
                ['Foyer', 78, 56, 22, 19],
            ],
        ],
        'plans-05' => [
            'alt' => 'Placeholder floor plan of an office floor around a central core',
            'height' => 60,
            'mirror' => false,
            'rooms' => [
                ['Open office', 0, 0, 62, 60, 'Column-free floor plate'],
                ['Meeting', 62, 0, 20, 18],
                ['Meeting', 82, 0, 18, 18],
                ['Core · Lifts', 62, 18, 20, 24],
                ['Office', 82, 18, 18, 24],
                ['Pantry', 62, 42, 20, 18],
                ['Washrooms', 82, 42, 18, 18],
            ],
        ],
        'plans-06' => [
            'alt' => 'Placeholder floor plan of a ground-floor shop',
            'height' => 50,
            'mirror' => false,
            'rooms' => [
                ['Shop floor', 0, 0, 70, 50, 'Glass frontage on the street side'],
                ['Store', 70, 0, 30, 25],
                ['Washroom', 70, 25, 15, 25],
                ['Office', 85, 25, 15, 25],
            ],
        ],
    ];

    /** @var list<Room> */
    private const array THREE_BED = [
        ['Bedroom 1', 0, 0, 32, 30, "14'0\" × 12'0\""],
        ['Bath', 32, 0, 13, 15],
        ['Bath', 32, 15, 13, 15],
        ['Bedroom 2', 45, 0, 28, 30, "12'0\" × 11'0\""],
        ['Bedroom 3', 73, 0, 27, 30, "11'6\" × 11'0\""],
        ['Balcony', 0, 30, 12, 40],
        ['Living', 12, 30, 38, 40, "20'0\" × 14'0\""],
        ['Dining', 50, 30, 25, 22, "12'0\" × 10'0\""],
        ['Bath', 75, 30, 12, 14],
        ['Store', 87, 30, 13, 14],
        ['Kitchen', 75, 44, 25, 26, "10'0\" × 8'0\""],
        ['Foyer', 50, 52, 25, 18],
    ];

    private const array WET = ['Bath', 'Kitchen', 'Utility', 'Washroom', 'Washrooms', 'Pantry', 'Dress', 'Store'];

    /**
     * @return list<string> files written, relative to storage/app/demo-src
     */
    public function handle(): array
    {
        $dir = DemoMedia::sourcePath('plans');
        File::ensureDirectoryExists($dir);

        $metaPath = $dir.'/meta.json';
        $existing = File::exists($metaPath) ? json_decode(File::get($metaPath), true) : [];
        /** @var array<string, array<string, mixed>> $meta */
        $meta = is_array($existing) ? $existing : [];
        $written = [];

        foreach (self::PLANS as $file => $plan) {
            if (File::glob($dir.'/'.$file.'.*') === []) {
                File::put("{$dir}/{$file}.svg", $this->drawing($plan['rooms'], $plan['height'], $plan['mirror']));
                $written[] = "plans/{$file}.svg";
            }

            $meta[$file] ??= [
                'alt' => $plan['alt'],
                'focal' => [50, 50],
                'caption' => null,
                'credit' => 'Placeholder plan',
                'decorative' => false,
            ];
        }

        File::put($metaPath, (string) json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $written;
    }

    /**
     * @param  list<Room>  $rooms
     */
    private function drawing(array $rooms, int $height, bool $mirror): string
    {
        $planWidth = 100 * self::SCALE;
        $planHeight = $height * self::SCALE;
        $width = $planWidth + 2 * self::MARGIN;
        $total = $planHeight + 2 * self::MARGIN + 40;
        $shapes = [];

        foreach ($rooms as $room) {
            [$label, $x, $y, $w, $h] = $room;
            $x = $mirror ? 100 - $x - $w : $x;
            [$px, $py, $pw, $ph] = [self::MARGIN + $x * self::SCALE, self::MARGIN + $y * self::SCALE, $w * self::SCALE, $h * self::SCALE];
            $fill = match (true) {
                $label === 'Balcony' => '#e8f1f2',
                in_array($label, self::WET, true) => '#f3f1ed',
                default => '#ffffff',
            };
            $size = $w < 16 ? 15 : 20;
            $cx = $px + $pw / 2;
            $cy = $py + $ph / 2;
            $text = htmlspecialchars($label, ENT_XML1);

            $shapes[] = "<rect x=\"{$px}\" y=\"{$py}\" width=\"{$pw}\" height=\"{$ph}\" fill=\"{$fill}\" stroke=\"#231f1e\" stroke-width=\"3\"/>";
            $shapes[] = "<text x=\"{$cx}\" y=\"{$cy}\" font-size=\"{$size}\" text-anchor=\"middle\" fill=\"#231f1e\">{$text}</text>";

            if (isset($room[5])) {
                $dims = htmlspecialchars($room[5], ENT_XML1);
                $shapes[] = '<text x="'.$cx.'" y="'.($cy + 26).'" font-size="15" text-anchor="middle" fill="#6b6461">'.$dims.'</text>';
            }
        }

        $outer = '<rect x="'.self::MARGIN.'" y="'.self::MARGIN."\" width=\"{$planWidth}\" height=\"{$planHeight}\" fill=\"none\" stroke=\"#231f1e\" stroke-width=\"10\"/>";
        $footerY = $planHeight + 2 * self::MARGIN + 14;
        $northX = $width - self::MARGIN - 14;
        $body = implode("\n  ", $shapes);
        $margin = self::MARGIN;

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$total}" width="{$width}" height="{$total}" font-family="Inter, 'Segoe UI', Arial, sans-serif">
              <rect width="{$width}" height="{$total}" fill="#ffffff"/>
              {$body}
              {$outer}
              <text x="{$margin}" y="{$footerY}" font-size="15" fill="#6b6461" letter-spacing="2">PLACEHOLDER PLAN · NOT TO SCALE</text>
              <path d="M{$northX} {$footerY} l-8 6 8-24 8 24z" fill="#231f1e"/>
              <text x="{$northX}" y="{$footerY}" dx="-30" font-size="15" fill="#231f1e">N</text>
            </svg>
            SVG;
    }
}
