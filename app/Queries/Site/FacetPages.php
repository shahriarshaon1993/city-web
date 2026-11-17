<?php

namespace App\Queries\Site;

use App\Models\FacetPage;
use App\Support\Catalog\CatalogCache;
use Illuminate\Support\Str;

/**
 * DM-FACET on the public side: the title, intro and meta description an editor set for a segment or
 * status landing page (PG-03), cached under the catalogue version. The intro is rich text in the
 * database, but the page shows it as plain text, so paragraphs are joined here.
 */
final class FacetPages
{
    /**
     * @return array{title: string, intro: string|null, meta_title: string|null, meta_description: string|null}|null
     */
    public static function find(string $kind, string $value): ?array
    {
        return CatalogCache::remember("facets:{$kind}:{$value}", CatalogCache::LISTING_TTL, function () use ($kind, $value): ?array {
            $page = FacetPage::query()->where('kind', $kind)->where('value', $value)->with('seoMeta')->first();

            if ($page === null) {
                return null;
            }

            $intro = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>'], ' ', (string) $page->intro)), ENT_QUOTES | ENT_HTML5)));

            return [
                'title' => $page->title,
                'intro' => $intro === '' ? null : $intro,
                'meta_title' => $page->seoMeta?->meta_title,
                'meta_description' => $page->seoMeta?->meta_description === null ? null : Str::limit($page->seoMeta->meta_description, 170, ''),
            ];
        });
    }
}
