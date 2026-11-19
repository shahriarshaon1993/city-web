<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Media\MediaImageResource;
use App\Models\MediaAsset;
use App\Queries\Site\ProjectCards;
use App\Queries\Site\ProjectDetail;
use App\Support\Format;
use App\Support\Media\MediaKind;
use App\Support\Seo\SeoData;
use App\Support\Settings\SharedSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only UI kit reference (0.2, 0.3, project cards 2.2) and the lead forms (7.1). Registered only when app()->isLocal(). The hero and content blocks are previewed in the page builder.
 */
class StyleguideController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // The sample photos are the first library images; a library without images shows the placeholders.
        $images = MediaAsset::query()->where('kind', MediaKind::Image->value)->with('media')->orderBy('id')->limit(8)->get()
            ->map(fn (MediaAsset $asset): array => MediaImageResource::make($asset)->resolve())->all();
        $pick = fn (int $position): ?array => $images[$position] ?? $images[0] ?? null;

        return Inertia::render('Site/Styleguide', [
            'images' => [
                'hero' => $pick(0),
                'hero_mobile' => $pick(0),
                'project' => $pick(1),
                'news' => $pick(2),
                'location' => $pick(3),
                'portrait' => $pick(4),
                'interior' => $pick(5),
                'thumbs' => [$pick(1), $pick(2), $pick(5), $pick(6)],
            ],
            'projects' => ProjectCards::filtered(null, null, 8),
            'ogImage' => SharedSettings::defaultOgImage(),
            'formatSamples' => $this->formatSamples(),
            'seo' => SeoData::page('Styleguide')->noindex(follow: false)->toArray($request),
        ]);
    }

    /**
     * Phase 7.1: the seven lead forms on one page, with a demo project that
     * has unit types.
     */
    public function forms(Request $request): Response
    {
        $project = ProjectDetail::find('gemcon-lakeview')['project'] ?? abort(404);

        return Inertia::render('Site/StyleguideForms', [
            'project' => [
                'slug' => $project['slug'],
                'name' => $project['name'],
                'unit_types' => array_map(
                    fn (array $unit): array => ['id' => $unit['id'], 'name' => $unit['name']],
                    $project['unit_types'],
                ),
            ],
            'seo' => SeoData::page('Lead forms')->noindex(follow: false)->toArray($request),
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
