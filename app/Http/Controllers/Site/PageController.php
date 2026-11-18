<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Queries\Site\PageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Content pages from the `pages` table (16.1): the system builder pages PG-01, 07–11 (`Site/Home`,
 * `Site/Page`, an ordered block list rendered by BlockRenderer), the legal pages PG-23/24
 * (`Site/Legal`) and the custom pages PG-25. ARC-001: thin; the shaping lives in `PageContent`.
 */
class PageController extends Controller
{
    public function home(Request $request): Response
    {
        return $this->system($request, Page::HOME);
    }

    public function about(Request $request): Response
    {
        return $this->system($request, 'about');
    }

    public function leadership(Request $request): Response
    {
        return $this->system($request, 'leadership');
    }

    public function services(Request $request): Response
    {
        return $this->system($request, 'services');
    }

    public function landowner(Request $request): Response
    {
        return $this->system($request, 'landowner');
    }

    public function nrb(Request $request): Response
    {
        return $this->system($request, 'nrb');
    }

    public function privacy(Request $request): Response
    {
        return $this->system($request, 'privacy-policy');
    }

    public function terms(Request $request): Response
    {
        return $this->system($request, 'terms');
    }

    /**
     * PG-25: any other slug. The route constraint keeps reserved slugs out (ReservedSlugs); a system
     * page is served only at its own address. A page that was renamed answers 301 to its new address
     * (SEO-TEC-003), anything else is a 404.
     */
    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $page = PageContent::find($slug);

        if ($page === null || $page->is_system) {
            $moved = Page::findBySlugHistory($slug);

            abort_unless($moved !== null && $moved->isPublished() && ! $moved->is_system, 404);

            return redirect($moved->path(), 301);
        }

        return $this->render($request, $page);
    }

    /**
     * CMS-PUB-005: the same template from a hydrated copy (pending revision or saved version).
     */
    public function preview(Request $request, Page $copy): Response
    {
        return $this->render($request, $copy);
    }

    private function system(Request $request, string $slug): Response
    {
        return $this->render($request, PageContent::find($slug) ?? abort(404));
    }

    private function render(Request $request, Page $page): Response
    {
        if ($page->isLegal()) {
            return Inertia::render('Site/Legal', PageContent::legalProps($request, $page));
        }

        return Inertia::render($page->slug === Page::HOME ? 'Site/Home' : 'Site/Page', PageContent::builderProps($request, $page));
    }
}
