<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Queries\Site\SiteSearch;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use App\Support\Site\SearchCopy;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-19 `/search?q=` (LAY-SEARCH): results grouped Projects · News · Pages,
 * at most 10 each (FR-SRCH-010, Scout database engine). A query under 2 characters
 * shows the minimum-length message instead of results. Always `noindex,follow`
 * (SEO-TEC-024; robots.txt disallows it from 12.3). 60 requests a minute per IP
 * (NFR-SEC-10, route middleware).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $copy = SearchCopy::copy();
        $query = SiteSearch::normalise($request->query('q'));
        $tooShort = $query !== '' && mb_strlen($query) < SiteSearch::MIN_LENGTH;
        $groups = $query === '' || $tooShort ? [] : SiteSearch::groups($query);

        $title = $query === '' || $tooShort ? $copy['title'] : str_replace('{q}', $query, $copy['results_title']);

        return Inertia::render('Site/Search', [
            'search' => [
                'query' => $query,
                'too_short' => $tooShort,
                'groups' => $groups,
                'total' => array_sum(array_column($groups, 'total')),
                'suggestions' => SiteSearch::suggestions(),
                'breadcrumbs' => Breadcrumbs::to($copy['title']),
            ],
            'copy' => $copy,
            'seo' => SeoData::page($title, $copy['description'])->noindex()->toArray($request),
        ]);
    }
}
