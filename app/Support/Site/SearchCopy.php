<?php

namespace App\Support\Site;

/**
 * Labels and copy of the search page PG-19 (CMS-000a). The results come from `Queries\Site\SiteSearch`.
 */
final class SearchCopy
{
    /**
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'title' => 'Search',
            'results_title' => 'Results for “{q}”',
            'description' => 'Search Gemcon City projects, news and pages.',
            'label' => 'Search the site',
            'placeholder' => 'Projects, areas, news…',
            'submit' => 'Search',
            'min_length' => 'Enter at least 2 characters.',
            'summary' => '{n} results for “{q}”',
            'summary_one' => '1 result for “{q}”',
            'projects_heading' => 'Projects',
            'projects_see_all' => 'See all in projects',
            'news_heading' => 'News',
            'news_see_all' => 'All news',
            'pages_heading' => 'Pages',
            'empty_title' => 'No results for “{q}”',
            'empty_body' => 'Check the spelling, or try a broader word such as an area name.',
            'suggestions_heading' => 'Popular areas',
            'suggestion_count' => '{n} projects',
        ];
    }
}
