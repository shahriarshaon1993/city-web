<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Queries\Site\ProjectDetail;
use App\Support\Catalog\CatalogCopy;
use App\Support\Demo\DemoContent;
use App\Support\Seo\ProjectMeta;
use App\Support\Seo\SeoOverrides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-04 `/projects/{slug}` (LAY-PROJ-DETAIL). A slug that used to belong to a project 301s to its
 * current page (SEO-TEC-003); a deleted project answers 301 to the page an editor chose, else 410
 * Gone for 90 days, then 404 (AC-PROJ-06). Drafts, scheduled and archived projects are 404.
 */
class ProjectController extends Controller
{
    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $page = ProjectDetail::find($slug);

        if ($page === null) {
            return $this->missing($slug);
        }

        $project = $page['project'];

        // HERO-012: the first gallery photo is the LCP image (gallery-hero preset).
        $seo = SeoOverrides::apply(
            $page['seo'],
            ProjectMeta::title($page['meta']),
            ProjectMeta::description($page['meta']),
            null,
            $project['cover']['alt'] ?? null,
        )->lcp($project['cover'], null, '(min-width:1440px) 1440px, 100vw');

        return Inertia::render('Site/Project', [
            'project' => $project,
            'copy' => CatalogCopy::project(),
            'cta' => DemoContent::projectListingCta(),
            'seo' => $seo->toArray($request),
        ]);
    }

    private function missing(string $slug): RedirectResponse
    {
        $current = ProjectDetail::movedTo($slug);

        if ($current !== null) {
            return redirect()->to('/projects/'.$current, 301);
        }

        $gone = ProjectDetail::gone($slug);

        if (is_string($gone)) {
            return redirect()->to($gone, 301);
        }

        abort($gone === true ? 410 : 404);
    }
}
