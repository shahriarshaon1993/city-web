<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoProjectDetail;
use App\Support\Seo\ProjectMeta;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-04 `/projects/{slug}` (LAY-PROJ-DETAIL). Unknown slugs are a 404; the
 * 410 / redirect rules of AC-PROJ-06 and slug history arrive with the
 * database in 13.3, which swaps DemoProjectDetail for ProjectDetailResource.
 */
class ProjectController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $project = DemoProjectDetail::find($slug);

        abort_if($project === null, 404);

        // HERO-012: the first gallery photo is the LCP image (gallery-hero preset).
        $seo = SeoData::raw(ProjectMeta::title($project), ProjectMeta::description($project))
            ->image(DemoProjectDetail::ogImage($slug), $project['cover']['alt'] ?? null)
            ->lcp($project['cover'], null, '(min-width:1440px) 1440px, 100vw');

        return Inertia::render('Site/Project', [
            'project' => $project,
            'copy' => DemoProjectDetail::copy(),
            'cta' => DemoContent::projectListingCta(),
            'seo' => $seo->toArray($request),
        ]);
    }
}
