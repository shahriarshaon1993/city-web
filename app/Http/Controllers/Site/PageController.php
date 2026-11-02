<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoPages;
use App\Support\Pages\LegalToc;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Content pages: the builder pages PG-07…11 and the custom pages PG-25
 * (`Site/Page`, an ordered block list rendered by BlockRenderer) and the legal
 * pages PG-23/24 (`Site/Legal`). ARC-001: thin; the content comes from
 * DemoPages until the page tables of 16.1.
 */
class PageController extends Controller
{
    public function about(Request $request): Response
    {
        return $this->builder($request, 'about');
    }

    public function leadership(Request $request): Response
    {
        return $this->builder($request, 'leadership');
    }

    public function services(Request $request): Response
    {
        return $this->builder($request, 'services');
    }

    public function landowner(Request $request): Response
    {
        return $this->builder($request, 'landowner');
    }

    public function nrb(Request $request): Response
    {
        return $this->builder($request, 'nrb');
    }

    /**
     * PG-25: any other slug. The route constraint keeps reserved slugs out
     * (ReservedSlugs); an unknown slug is a 404.
     */
    public function show(Request $request, string $slug): Response
    {
        return $this->builder($request, $slug);
    }

    public function privacy(Request $request): Response
    {
        return $this->legal($request, 'privacy-policy');
    }

    public function terms(Request $request): Response
    {
        return $this->legal($request, 'terms');
    }

    private function builder(Request $request, string $key): Response
    {
        $page = DemoPages::builder($key) ?? abort(404);
        $blocks = $page['blocks'];

        $seo = SeoData::page($page['title'], $page['description'])->lcpFromBlocks($blocks);

        if (! $page['index']) {
            $seo->noindex();
        }

        return Inertia::render('Site/Page', [
            'blocks' => $blocks,
            // CMP-HEAD-01: the header starts transparent over a hero or a photo page header.
            'starts_with_hero' => $this->startsWithHero($blocks),
            'seo' => $seo->toArray($request),
        ]);
    }

    private function legal(Request $request, string $key): Response
    {
        $page = DemoPages::legal($key) ?? abort(404);
        $article = LegalToc::build($page['body']);

        return Inertia::render('Site/Legal', [
            'title' => $page['title'],
            'updated_at' => $page['updated_at'],
            'body' => $article['body'],
            'toc' => $article['toc'],
            'breadcrumbs' => Breadcrumbs::to($page['title']),
            'seo' => SeoData::page($page['title'], $page['description'])->toArray($request),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function startsWithHero(array $blocks): bool
    {
        $first = $blocks[0] ?? null;

        return match ($first['type'] ?? null) {
            'hero' => true,
            'page_header' => ($first['data']['image'] ?? null) !== null,
            default => false,
        };
    }
}
