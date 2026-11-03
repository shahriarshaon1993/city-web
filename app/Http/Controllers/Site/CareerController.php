<?php

namespace App\Http\Controllers\Site;

use App\Actions\Careers\SubmitJobApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\JobApplicationRequest;
use App\Support\Demo\DemoCareers;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-15 `/careers` (LAY-CAREERS) and PG-16 `/careers/{slug}`
 * (LAY-CAREER-DETAIL). Filters are query links (`?department=…&location=…
 * &type=…`, owner 8.2); a filtered view is `noindex,follow` with its
 * canonical on `/careers`, and an unknown filter value 301s away. A closed
 * job shows "Applications closed" for 30 days, then 410 (FR-CAR-003). The
 * apply form and the CV drop post to `apply` and show their own success
 * state (owner 8.2: no thank-you page, a job application is not a lead).
 */
class CareerController extends Controller
{
    /** URL parameter → job field, in URL order. */
    private const array FACETS = ['department' => 'department', 'location' => 'location', 'type' => 'type'];

    public function index(Request $request): Response|RedirectResponse
    {
        $options = $this->facetOptions();
        $active = [];

        foreach (array_keys(self::FACETS) as $facet) {
            $value = $request->query($facet);

            if ($value !== null && (! is_string($value) || ! isset($options[$facet][$value]))) {
                return redirect()->to($this->cleanUrl($request, $facet), 301);
            }

            if ($value !== null) {
                $active[$facet] = $value;
            }
        }

        $copy = DemoCareers::copy();
        // Local preview of the "no openings" state with the CV drop (FR-CAR-004, owner 8.2).
        $open = app()->isLocal() && $request->query('preview') === 'empty' ? [] : DemoCareers::open();
        $roles = array_values(array_filter($open, fn (array $job): bool => $this->matches($job, $active)));
        $blocks = DemoCareers::introBlocks();

        $seo = SeoData::page($copy['title'], $copy['description'])->lcpFromBlocks($blocks);

        if ($active !== []) {
            $seo->noindex()->canonical(SeoData::absoluteUrl('/careers'));
        }

        return Inertia::render('Site/Careers', [
            'blocks' => $blocks,
            'careers' => [
                'roles' => array_map(DemoCareers::row(...), $roles),
                'total_open' => count($open),
                'filtered' => $active !== [],
                'facets' => $open === [] ? [] : $this->facets($open, $options, $active),
            ],
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $job = DemoCareers::find($slug) ?? abort(404);

        abort_if(DemoCareers::isGone($job), 410);

        $copy = DemoCareers::copy();
        $detail = DemoCareers::detail($job);
        $seo = SeoData::page($job['title'], $job['summary']);

        // PG-16: indexable while open only.
        if (! $detail['is_open']) {
            $seo->noindex();
        }

        $others = array_filter(DemoCareers::open(), fn (array $other): bool => $other['slug'] !== $slug);

        return Inertia::render('Site/CareerJob', [
            'job' => $detail,
            'other_roles' => $detail['is_open'] ? [] : array_map(DemoCareers::row(...), array_slice(array_values($others), 0, 4)),
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    public function apply(JobApplicationRequest $request, SubmitJobApplication $submit): RedirectResponse
    {
        $submit->handle($request->validated());

        return back();
    }

    /**
     * Allowed values per facet: every department, location and type that
     * has an open role.
     *
     * @return array<string, array<string, string>>
     */
    private function facetOptions(): array
    {
        $options = ['department' => [], 'location' => [], 'type' => []];
        $locations = DemoCareers::locations();

        foreach (DemoCareers::open() as $job) {
            $options['department'][$job['department']] = DemoCareers::DEPARTMENTS[$job['department']];
            $options['location'][DemoCareers::locationSlug($job)] = $locations[DemoCareers::locationSlug($job)];
            $options['type'][$this->typeValue($job['type'])] = DemoCareers::TYPE_LABELS[$job['type']];
        }

        // Keep the source order of departments, locations and types.
        $order = [
            'department' => array_keys(DemoCareers::DEPARTMENTS),
            'location' => array_keys($locations),
            'type' => array_map($this->typeValue(...), array_keys(DemoCareers::TYPE_LABELS)),
        ];
        $sorted = [];

        foreach ($order as $facet => $values) {
            $sorted[$facet] = [];

            foreach ($values as $value) {
                if (isset($options[$facet][$value])) {
                    $sorted[$facet][$value] = $options[$facet][$value];
                }
            }
        }

        return $sorted;
    }

    /**
     * One facet row each (overline label, "All", options with counts): a
     * count respects the other active facets; options with no match are
     * shown disabled (FR-SRCH-004 style).
     *
     * @param  list<array<string, mixed>>  $open
     * @param  array<string, array<string, string>>  $options
     * @param  array<string, string>  $active
     * @return list<array<string, mixed>>
     */
    private function facets(array $open, array $options, array $active): array
    {
        $copy = DemoCareers::copy();
        $rows = [];

        foreach (array_keys(self::FACETS) as $facet) {
            $others = array_diff_key($active, [$facet => true]);
            $pool = array_filter($open, fn (array $job): bool => $this->matches($job, $others));

            $rows[] = [
                'key' => $facet,
                'label' => $copy["{$facet}_label"],
                'all' => ['label' => $copy['all_label'], 'count' => count($pool), 'url' => $this->url($others), 'active' => ! isset($active[$facet])],
                'options' => array_map(
                    fn (string $value, string $label): array => [
                        'value' => $value,
                        'label' => $label,
                        'count' => count(array_filter($pool, fn (array $job): bool => $this->matches($job, [$facet => $value]))),
                        'url' => $this->url([...$others, $facet => $value]),
                        'active' => ($active[$facet] ?? null) === $value,
                    ],
                    array_map(strval(...), array_keys($options[$facet])),
                    array_values($options[$facet]),
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $job
     * @param  array<string, string>  $filters
     */
    private function matches(array $job, array $filters): bool
    {
        $values = [
            'department' => $job['department'],
            'location' => DemoCareers::locationSlug($job),
            'type' => $this->typeValue($job['type']),
        ];

        foreach ($filters as $facet => $value) {
            if ($values[$facet] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * `/careers?department=…&location=…&type=…` in a fixed order.
     *
     * @param  array<string, string>  $filters
     */
    private function url(array $filters): string
    {
        $ordered = array_filter(array_map(fn (string $facet): ?string => $filters[$facet] ?? null, array_combine(array_keys(self::FACETS), array_keys(self::FACETS))));

        return $ordered === [] ? '/careers' : '/careers?'.http_build_query($ordered);
    }

    /**
     * The request URL without one parameter (other parameters, e.g. UTM, pass through).
     */
    private function cleanUrl(Request $request, string $without): string
    {
        $query = array_diff_key($request->query(), [$without => true]);

        return '/careers'.($query === [] ? '' : '?'.http_build_query($query));
    }

    /** `full_time` → `full-time` (URL value). */
    private function typeValue(string $type): string
    {
        return str_replace('_', '-', $type);
    }
}
