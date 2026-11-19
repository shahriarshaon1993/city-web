<?php

namespace App\Http\Controllers\Site;

use App\Actions\Careers\SubmitJobApplication;
use App\Enums\EmploymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\JobApplicationRequest;
use App\Http\Resources\Site\JobPostResource;
use App\Http\Resources\Site\JobRowResource;
use App\Models\JobPost;
use App\Queries\Site\CareersQuery;
use App\Queries\Site\PageContent;
use App\Support\Careers\CareersCopy;
use App\Support\Leads\LeadContext;
use App\Support\Seo\SeoData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * PG-15 `/careers` (LAY-CAREERS) and PG-16 `/careers/{slug}` (LAY-CAREER-DETAIL), read from `job_posts`.
 * Filters are query links (`?department=…&location=…&type=…`, owner 8.2); a filtered view is
 * `noindex,follow` with its canonical on `/careers`, and an unknown filter value 301s away. A closed
 * job shows "Applications closed" for 30 days, then 410 (FR-CAR-003); a draft is a 404 unless the link
 * is the signed preview of the admin (HELP-04). The apply form and the CV drop post to `apply` and show
 * their own success state (owner 8.2: no thank-you page, a job application is not a lead).
 */
class CareerController extends Controller
{
    /** URL parameter → job field, in URL order. */
    private const array FACETS = ['department' => 'department', 'location' => 'location', 'type' => 'type'];

    public function index(Request $request): Response|RedirectResponse
    {
        // Local preview of the "no openings" state with the CV drop (FR-CAR-004, owner 8.2).
        $jobs = app()->isLocal() && $request->query('preview') === 'empty' ? new Collection : CareersQuery::open();
        $values = $this->valuesOf($jobs);
        $options = $this->facetOptions($jobs, $values);
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

        $copy = CareersCopy::labels();
        $roles = $jobs->filter(fn (JobPost $job): bool => $this->matches($values[$job->id], $active))->values();
        $blocks = PageContent::blocksOf(PageContent::find('careers'));

        $seo = SeoData::page($copy['title'], $copy['description'])->lcpFromBlocks($blocks);

        if ($active !== []) {
            $seo->noindex()->canonical(SeoData::absoluteUrl('/careers'));
        }

        return Inertia::render('Site/Careers', [
            'blocks' => $blocks,
            'careers' => [
                'roles' => JobRowResource::collection($roles)->resolve($request),
                'total_open' => $jobs->count(),
                'filtered' => $active !== [],
                'facets' => $jobs->isEmpty() ? [] : $this->facets($jobs, $values, $options, $active),
            ],
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ]);
    }

    public function show(Request $request, string $slug): HttpResponse
    {
        // HELP-04: the admin's signed, 60-minute link shows a draft or a gone job. Nothing is written.
        $preview = $request->hasValidSignature();
        $job = $preview ? CareersQuery::findAny($slug) : CareersQuery::find($slug);

        if ($job === null) {
            $moved = JobPost::findBySlugHistory($slug);

            abort_unless($moved !== null && $moved->status->value !== 'draft', 404);

            return redirect($moved->path(), 301);
        }

        abort_if(! $preview && $job->isGone(), 410);

        $copy = CareersCopy::labels();
        $detail = JobPostResource::make($job)->resolve($request);
        $seo = $this->seoFor($job);

        // PG-16: indexable while open only.
        if (! $detail['is_open'] || $preview) {
            $seo->noindex($preview ? false : true);
        }

        if ($preview) {
            $request->attributes->set('preview', [
                'label' => $job->status->label().' job post',
                'expires' => Carbon::createFromTimestamp((int) $request->query('expires'))->timezone('Asia/Dhaka')->format('H:i'),
            ]);
        }

        $response = Inertia::render('Site/CareerJob', [
            'job' => $detail,
            'other_roles' => $detail['is_open'] ? [] : JobRowResource::collection(CareersQuery::others($job))->resolve($request),
            'copy' => $copy,
            'seo' => $seo->toArray($request),
        ])->toResponse($request);

        if ($preview) {
            $response->headers->add(['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
        }

        return $response;
    }

    /** FR-CAR-002: the apply form and the CV drop. The route runs the honeypot and the rate limit first. */
    public function apply(JobApplicationRequest $request, SubmitJobApplication $submit): RedirectResponse
    {
        $submit->handle($request->validated(), LeadContext::fromRequest($request));

        return back();
    }

    /** The SEO panel of the job wins over the page template (spec/cms.md §8.5). */
    private function seoFor(JobPost $job): SeoData
    {
        $meta = $job->seoMeta;
        $description = filled($meta?->meta_description) ? (string) $meta->meta_description : $job->summary;
        $seo = filled($meta?->meta_title) ? SeoData::raw((string) $meta->meta_title, $description) : SeoData::page($job->title, $description);

        if (filled($meta?->robots) && $meta->robots !== SeoData::ROBOTS_INDEX) {
            $seo->robots($meta->robots);
        }

        if (filled($meta?->canonical_url)) {
            $seo->canonical($meta->canonical_url);
        }

        if ($meta?->ogImage !== null) {
            $seo->image($meta->ogImage->ogUrl());
        }

        return $seo;
    }

    /**
     * The URL value of each facet for every job.
     *
     * @param  Collection<int, JobPost>  $jobs
     * @return array<int, array{department: string, location: string, type: string}>
     */
    private function valuesOf(Collection $jobs): array
    {
        $offices = CareersQuery::officeSlugs();
        $values = [];

        foreach ($jobs as $job) {
            $values[$job->id] = [
                'department' => CareersQuery::departmentSlug($job->department),
                'location' => $job->office_id === null ? CareersQuery::SITES_SLUG : ($offices[$job->office_id] ?? CareersQuery::SITES_SLUG),
                'type' => $job->employment_type->urlValue(),
            ];
        }

        return $values;
    }

    /**
     * Allowed values per facet (URL value → label): every department, location and type that has an
     * open role, in a stable order — departments by name, offices in their own order, types as listed.
     *
     * @param  Collection<int, JobPost>  $jobs
     * @param  array<int, array{department: string, location: string, type: string}>  $values
     * @return array<string, array<string, string>>
     */
    private function facetOptions(Collection $jobs, array $values): array
    {
        $options = ['department' => [], 'location' => [], 'type' => []];

        foreach ($jobs as $job) {
            $options['department'][$values[$job->id]['department']] ??= $job->department;
            $options['location'][$values[$job->id]['location']] ??= $job->office->name ?? CareersQuery::SITES_LABEL;
            $options['type'][$values[$job->id]['type']] ??= $job->employment_type->label();
        }

        asort($options['department'], SORT_NATURAL | SORT_FLAG_CASE);

        $sites = $options['location'][CareersQuery::SITES_SLUG] ?? null;
        unset($options['location'][CareersQuery::SITES_SLUG]);
        $offices = array_flip(array_values(CareersQuery::officeSlugs()));
        uksort($options['location'], fn (string $a, string $b): int => ($offices[$a] ?? PHP_INT_MAX) <=> ($offices[$b] ?? PHP_INT_MAX));

        if ($sites !== null) {
            $options['location'][CareersQuery::SITES_SLUG] = $sites;
        }

        $order = array_map(fn (EmploymentType $type): string => $type->urlValue(), EmploymentType::cases());
        uksort($options['type'], fn (string $a, string $b): int => array_search($a, $order, true) <=> array_search($b, $order, true));

        return $options;
    }

    /**
     * One facet row each (overline label, "All", options with counts): a count respects the other active
     * facets; options with no match are shown disabled (FR-SRCH-004 style).
     *
     * @param  Collection<int, JobPost>  $jobs
     * @param  array<int, array{department: string, location: string, type: string}>  $values
     * @param  array<string, array<string, string>>  $options
     * @param  array<string, string>  $active
     * @return list<array<string, mixed>>
     */
    private function facets(Collection $jobs, array $values, array $options, array $active): array
    {
        $copy = CareersCopy::labels();
        $rows = [];

        foreach (array_keys(self::FACETS) as $facet) {
            $others = array_diff_key($active, [$facet => true]);
            $pool = $jobs->filter(fn (JobPost $job): bool => $this->matches($values[$job->id], $others));

            $rows[] = [
                'key' => $facet,
                'label' => $copy["{$facet}_label"],
                'all' => ['label' => $copy['all_label'], 'count' => $pool->count(), 'url' => $this->url($others), 'active' => ! isset($active[$facet])],
                'options' => array_map(
                    fn (string $value, string $label): array => [
                        'value' => $value,
                        'label' => $label,
                        'count' => $pool->filter(fn (JobPost $job): bool => $values[$job->id][$facet] === $value)->count(),
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
     * @param  array{department: string, location: string, type: string}  $values
     * @param  array<string, string>  $filters
     */
    private function matches(array $values, array $filters): bool
    {
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
}
