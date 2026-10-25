<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoMedia;
use App\Support\Format;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only UI kit reference (0.2, 0.3) and hero previews (2.1). Registered only when app()->isLocal().
 */
class StyleguideController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Site/Styleguide', [
            'images' => [
                'hero' => DemoMedia::image('hero/hero-01'),
                'hero_mobile' => DemoMedia::image('hero/hero-01-mobile'),
                'project' => DemoMedia::image('projects/projects-01'),
                'news' => DemoMedia::image('news/news-01'),
                'location' => DemoMedia::image('locations/locations-01'),
                'portrait' => DemoMedia::image('team/team-01'),
                'interior' => DemoMedia::image('interiors/interiors-01'),
                'thumbs' => array_map(DemoMedia::image(...), ['projects/projects-02', 'projects/projects-03', 'interiors/interiors-02', 'progress/progress-01']),
            ],
            'ogImage' => DemoMedia::ogImage('hero/hero-01'),
            'formatSamples' => $this->formatSamples(),
            'seo' => SeoData::page('Styleguide')->noindex(follow: false)->toArray($request),
        ]);
    }

    /**
     * Phase 2.1 hero previews: the home page with another hero variant
     * (image, video, slider, centered).
     */
    public function hero(Request $request, string $variant): Response
    {
        $blocks = DemoContent::homeBlocks($variant);

        return Inertia::render('Site/Home', [
            'blocks' => $blocks,
            'lead' => DemoContent::page('home')['intro'],
            'starts_with_hero' => true,
            'seo' => SeoData::page('Hero: '.$variant)->noindex(follow: false)->lcpFromBlocks($blocks)->toArray($request),
        ]);
    }

    /**
     * The same calls run through resources/js/lib/format.ts on the page; both
     * columns must match, and match the spec strings (ARC-016, ARC-017).
     *
     * @return list<array{fn: string, args: list<int|float|string|bool|null>, php: string, expected: string|null}>
     */
    private function formatSamples(): array
    {
        $samples = [
            ['formatBdt', [14_500_000], '৳ 1,45,00,000'],
            ['formatBdtCompact', [14_500_000], '৳ 1.45 crore'],
            ['formatBdtCompact', [8_500_000], '৳ 85 lakh'],
            ['formatBdtCompact', [9_999_999], null],
            ['formatBdtCompact', [125_000_000], null],
            ['formatBdtCompact', [95_000], null],
            ['formatBdtRange', [8_500_000, 9_500_000], null],
            ['formatBdtRange', [8_500_000, 14_500_000], null],
            ['formatBdtRange', [8_500_000, 14_500_000, false], null],
            ['formatSft', [1_850], '1,850 sft'],
            ['formatSftRange', [1_650, 2_100], '1,650–2,100 sft'],
            ['formatKatha', [5], '5 katha'],
            ['formatKatha', [3.5], null],
            ['formatDate', ['2026-10-25T05:00:00.000000Z'], '25 Oct 2026'],
            ['formatDate', ['2026-10-25T19:30:00.000000Z'], null],
            ['formatMonth', ['2027-12-01'], null],
            ['formatPhone', ['+8809600000000'], '+880 9600-000000'],
            ['formatPhone', ['+14155550100'], '+14155550100'],
        ];

        $php = [
            'formatBdt' => Format::bdt(...),
            'formatBdtCompact' => Format::bdtCompact(...),
            'formatBdtRange' => Format::bdtRange(...),
            'formatSft' => Format::sft(...),
            'formatSftRange' => Format::sftRange(...),
            'formatKatha' => Format::katha(...),
            'formatDate' => Format::date(...),
            'formatMonth' => Format::month(...),
            'formatPhone' => Format::phone(...),
        ];

        return array_values(array_map(fn (array $sample): array => [
            'fn' => $sample[0],
            'args' => $sample[1],
            'php' => $php[$sample[0]](...$sample[1]),
            'expected' => $sample[2],
        ], $samples));
    }
}
