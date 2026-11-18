<?php

namespace App\Queries\Site;

use App\Models\Page;
use App\Support\Blocks\BlockResolver;
use App\Support\Pages\LegalToc;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use App\Support\Seo\SeoOverrides;
use App\Support\Settings\Settings;
use Illuminate\Http\Request;

/**
 * The public read side of pages (PG-01, 07–11, 15 intro, 23–25): a published page by slug, its blocks
 * resolved to the props `BlockRenderer` draws (16.1), and the head built from the page and its SEO
 * panel. The same shaping serves a hydrated copy for the preview (CMS-PUB-005).
 */
final class PageContent
{
    /** A live (published, not in the bin) page by slug, with what rendering needs. */
    public static function find(string $slug): ?Page
    {
        return Page::query()->published()->where('slug', $slug)
            ->with(['blocks', 'seoMeta.ogImage.media'])->first();
    }

    /**
     * The page's visible blocks as public props; empty for a page that does not exist.
     *
     * @return list<array<string, mixed>>
     */
    public static function blocksOf(?Page $page): array
    {
        return $page === null ? [] : BlockResolver::resolve($page, $page->blocks);
    }

    /**
     * Props of a builder page.
     *
     * @return array{blocks: list<array<string, mixed>>, starts_with_hero: bool, seo: array<string, mixed>}
     */
    public static function builderProps(Request $request, Page $page): array
    {
        $blocks = self::blocksOf($page);

        return [
            'blocks' => $blocks,
            // CMP-HEAD-01: the header starts transparent over a hero or a photo page header.
            'starts_with_hero' => self::startsWithHero($blocks),
            'seo' => self::seo($page)->lcpFromBlocks($blocks)->toArray($request),
        ];
    }

    /**
     * Props of a legal page (LAY-LEGAL): its first rich-text block is the text.
     *
     * @return array<string, mixed>
     */
    public static function legalProps(Request $request, Page $page): array
    {
        $body = '';

        foreach ($page->blocks as $block) {
            if ($block->is_visible && $block->type === 'rich_text') {
                $body = (string) ($block->data['body'] ?? '');

                break;
            }
        }

        $article = LegalToc::build($body);

        return [
            'title' => $page->title,
            'updated_at' => ($page->last_reviewed_at ?? $page->updated_at)?->toDateString(),
            'body' => $article['body'],
            'toc' => $article['toc'],
            'breadcrumbs' => Breadcrumbs::to($page->title),
            'seo' => self::seo($page)->toArray($request),
        ];
    }

    /**
     * §9.3 templates with what the editor typed in the SEO panel on top (DM-SEO).
     */
    public static function seo(Page $page): SeoData
    {
        $settings = Settings::group('seo');
        $meta = $page->seoMeta;
        $home = $page->slug === Page::HOME;

        return SeoOverrides::apply(
            [
                'meta_title' => $meta?->meta_title,
                'meta_description' => $meta?->meta_description,
                'robots' => $meta?->robots,
                'canonical_url' => $meta?->canonical_url,
                'og_image' => $meta?->ogImage?->ogUrl(),
            ],
            $home ? (string) $settings['home_title'] : str_replace('{title}', $page->title, (string) $settings['title_template']),
            $home ? (string) $settings['home_description'] : '',
            null,
            $meta?->ogImage?->alt,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private static function startsWithHero(array $blocks): bool
    {
        $first = $blocks[0] ?? null;

        return match ($first['type'] ?? null) {
            'hero' => true,
            'page_header' => ($first['data']['image'] ?? null) !== null,
            default => false,
        };
    }
}
