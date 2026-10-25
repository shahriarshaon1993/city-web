<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-01: an ordered block list (CMS-PB-001), hero first (ARC-001: thin).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $blocks = DemoContent::homeBlocks();

        return Inertia::render('Site/Home', [
            'blocks' => $blocks,
            // CMP-HEAD-01: the header starts transparent over a first-block hero.
            'starts_with_hero' => true,
            'seo' => SeoData::home()->lcpFromBlocks($blocks)->toArray($request),
        ]);
    }
}
