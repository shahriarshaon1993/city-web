<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoProjects;
use App\Support\Demo\DemoShortlist;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-20 `/shortlist?ids=` and PG-21 `/compare?ids=` (FR-SHORT-002/003). The
 * IDs live in the visitor's browser, so the page visits itself with them
 * (Inertia) and gets back only the published ones; the page then drops the
 * rest from storage. Both pages are `noindex,nofollow` (PG-20/21).
 */
class ShortlistController extends Controller
{
    public function index(Request $request): Response
    {
        $copy = DemoShortlist::copy();
        $rows = DemoShortlist::rows(DemoShortlist::ids($request->query('ids'), DemoShortlist::SHORTLIST_MAX));

        return Inertia::render('Site/Shortlist', [
            'shortlist' => [
                'requested' => $request->has('ids'),
                'ids' => array_column($rows, 'id'),
                'projects' => array_map(DemoProjects::card(...), $rows),
                'breadcrumbs' => Breadcrumbs::to($copy['shortlist_title']),
            ],
            'copy' => $copy,
            'seo' => SeoData::page($copy['shortlist_title'], $copy['shortlist_description'])->noindex(follow: false)->toArray($request),
        ]);
    }

    public function compare(Request $request): Response
    {
        $copy = DemoShortlist::copy();
        $rows = DemoShortlist::rows(DemoShortlist::ids($request->query('ids'), DemoShortlist::COMPARE_MAX));

        return Inertia::render('Site/Compare', [
            'compare' => [
                'requested' => $request->has('ids'),
                'ids' => array_column($rows, 'id'),
                'projects' => array_map(DemoProjects::card(...), $rows),
                'groups' => $rows === [] ? [] : DemoShortlist::compare($rows),
                'breadcrumbs' => Breadcrumbs::to($copy['compare_title']),
            ],
            'copy' => $copy,
            'seo' => SeoData::page($copy['compare_title'], $copy['compare_description'])->noindex(follow: false)->toArray($request),
        ]);
    }
}
