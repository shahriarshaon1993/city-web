<?php

namespace App\Support\Locations;

use App\Models\Location;
use App\Models\Project;
use App\Support\Html\BasicHtml;

/**
 * FR-LOC-003: an area page is indexable only with at least one published project and an intro of
 * at least 150 words; otherwise it renders `noindex,follow` and stays out of the sitemaps. The admin
 * shows the verdict and the reasons (ADM-SCR-04).
 */
final class Indexability
{
    public const int MIN_INTRO_WORDS = 150;

    /**
     * @return array{indexable: bool, reasons: list<string>, intro_words: int, published_projects: int}
     */
    public function for(Location $area, ?int $publishedProjects = null): array
    {
        $words = BasicHtml::wordCount($area->intro);
        $projects = $publishedProjects ?? Project::query()->published()->where('location_id', $area->id)->count();
        $reasons = [];

        if ($projects === 0) {
            $reasons[] = 'No published project in this area yet.';
        }

        if ($words < self::MIN_INTRO_WORDS) {
            $reasons[] = "The intro has {$words} of the ".self::MIN_INTRO_WORDS.' words it needs.';
        }

        return ['indexable' => $reasons === [], 'reasons' => $reasons, 'intro_words' => $words, 'published_projects' => $projects];
    }
}
