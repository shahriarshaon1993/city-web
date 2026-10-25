<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoMedia;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-01. A stub until the home blocks of phase 2 (ARC-001: thin).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $page = DemoContent::page('home');
        $hero = DemoMedia::image('hero/hero-01');
        $heroMobile = DemoMedia::image('hero/hero-01-mobile');

        return Inertia::render('Site/Home', [
            'title' => $page['title'],
            'lead' => $page['intro'],
            'hero' => ['image' => $hero, 'mobile' => $heroMobile],
            // CMP-HEAD-01: the header starts transparent over a first-block hero.
            'starts_with_hero' => true,
            'seo' => SeoData::home()->lcp($hero, $heroMobile)->toArray($request),
        ]);
    }
}
