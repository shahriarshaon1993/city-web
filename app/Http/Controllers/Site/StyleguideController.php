<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoMedia;
use App\Support\Format;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only UI kit reference (0.2, 0.3). Registered only when app()->isLocal().
 */
class StyleguideController extends Controller
{
    public function __invoke(): Response
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
        ];

        return array_values(array_map(fn (array $sample): array => [
            'fn' => $sample[0],
            'args' => $sample[1],
            'php' => $php[$sample[0]](...$sample[1]),
            'expected' => $sample[2],
        ], $samples));
    }
}
