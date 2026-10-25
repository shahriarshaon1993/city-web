<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stub content pages until the builder pages of 6.3 (ARC-001: thin).
 */
class PageController extends Controller
{
    public function about(Request $request): Response
    {
        $page = DemoContent::page('about');

        return Inertia::render('Site/Page', [
            'title' => $page['title'],
            'intro' => $page['intro'],
            'seo' => SeoData::page($page['title'], $page['description'])->toArray($request),
        ]);
    }
}
