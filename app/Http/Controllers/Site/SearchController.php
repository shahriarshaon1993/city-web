<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoSiteSearch;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-19 `/search?q=` (LAY-SEARCH): results grouped Projects · News · Pages,
 * at most 10 each (FR-SRCH-010). A query under 2 characters shows the
 * minimum-length message instead of results. Always `noindex,follow`
 * (SEO-TEC-024; robots.txt disallows it from 12.3).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $copy = DemoSiteSearch::copy();
        $raw = $request->query('q');
        $query = Str::limit(is_string($raw) ? trim(preg_replace('/\s+/u', ' ', $raw) ?? '') : '', DemoSiteSearch::MAX_LENGTH, '');
        $tooShort = $query !== '' && mb_strlen($query) < DemoSiteSearch::MIN_LENGTH;
        $groups = $query === '' || $tooShort ? [] : DemoSiteSearch::search($query);

        $title = $query === '' || $tooShort ? $copy['title'] : str_replace('{q}', $query, $copy['results_title']);

        return Inertia::render('Site/Search', [
            'search' => [
                'query' => $query,
                'too_short' => $tooShort,
                'groups' => $groups,
                'total' => array_sum(array_column($groups, 'total')),
                'suggestions' => DemoSiteSearch::suggestions(),
                'breadcrumbs' => Breadcrumbs::to($copy['title']),
            ],
            'copy' => $copy,
            'seo' => SeoData::page($title, $copy['description'])->noindex()->toArray($request),
        ]);
    }
}
