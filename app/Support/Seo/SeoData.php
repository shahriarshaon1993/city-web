<?php

namespace App\Support\Seo;

use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoMedia;
use Illuminate\Http\Request;

/**
 * The `seo` page prop (resources/js/types/site.ts `Seo`): rendered into the
 * public head by app.blade.php (SEO-REN-01) and mirrored by SeoHead.vue.
 * Settings come from DemoContent until 12.1.
 *
 * @phpstan-type LcpImage array{image: array<string, mixed>, mobile: array<string, mixed>|null, sizes: string}
 * @phpstan-type ImagePreload array{srcset: string, sizes: string, type: string, media: string|null}
 */
final class SeoData
{
    public const string ROBOTS_INDEX = 'index,follow';

    public const string ROBOTS_NOINDEX = 'noindex,follow';

    public const string ROBOTS_NONE = 'noindex,nofollow';

    private string $robots = self::ROBOTS_INDEX;

    private ?string $canonical = null;

    private bool $canonicalSet = false;

    private string $ogType = 'website';

    private ?string $image = null;

    private ?string $imageAlt = null;

    /** @var list<array<string, mixed>> */
    private array $jsonLd = [];

    /** @var LcpImage|null */
    private ?array $lcpImage = null;

    private function __construct(
        private readonly string $title,
        private readonly ?string $description,
    ) {}

    /**
     * Entity/page title through the SEO title template (SEO-ONP-001).
     */
    public static function page(string $title, ?string $description = null): self
    {
        $template = (string) DemoContent::settings('seo')['title_template'];

        return new self(str_replace('{title}', $title, $template), self::clean($description));
    }

    /**
     * Home title and description straight from Settings → SEO (SEO-ONP-001).
     */
    public static function home(): self
    {
        $seo = DemoContent::settings('seo');

        return new self((string) $seo['home_title'], self::clean($seo['home_description']));
    }

    /**
     * Final title used as is, e.g. an entity's own meta-title override.
     */
    public static function raw(string $title, ?string $description = null): self
    {
        return new self($title, self::clean($description));
    }

    public function robots(string $robots): self
    {
        $this->robots = $robots;

        return $this;
    }

    public function noindex(bool $follow = true): self
    {
        return $this->robots($follow ? self::ROBOTS_NOINDEX : self::ROBOTS_NONE);
    }

    /**
     * Override the canonical URL (absolute); defaults to the clean request URL.
     */
    public function canonical(?string $url): self
    {
        $this->canonical = $url;
        $this->canonicalSet = true;

        return $this;
    }

    /**
     * Absolute 1200×630 OG image URL (SEO-ONP-006); defaults to the site OG image.
     */
    public function image(?string $url, ?string $alt = null): self
    {
        $this->image = $url;
        $this->imageAlt = $alt;

        return $this;
    }

    public function article(): self
    {
        $this->ogType = 'article';

        return $this;
    }

    /**
     * The page's LCP image, preloaded by the root view (HERO-030/031).
     *
     * @param  array<string, mixed>|null  $image  MediaImage
     * @param  array<string, mixed>|null  $mobile  MediaImage crop for < 768 px
     * @param  string  $sizes  the image's `sizes` preset (design/foundations.md §5.3)
     */
    public function lcp(?array $image, ?array $mobile = null, string $sizes = '100vw'): self
    {
        $this->lcpImage = $image === null ? null : ['image' => $image, 'mobile' => $mobile, 'sizes' => $sizes];

        return $this;
    }

    /**
     * The LCP image of a block page: the first block's hero image, video
     * poster (HERO-021) or first slide (CMP-HERO-03); none for other blocks.
     *
     * @param  list<array<string, mixed>>  $blocks  DM-BLOCK shape
     */
    public function lcpFromBlocks(array $blocks): self
    {
        $first = $blocks[0] ?? null;

        if (($first['type'] ?? null) !== 'hero' || ! is_array($first['data'] ?? null)) {
            return $this->lcp(null);
        }

        $hero = $first['data'];
        $media = $hero['mode'] === 'slider' ? ($hero['slides'][0] ?? []) : $hero;

        return $this->lcp($media['image'] ?? null, $media['mobile_image'] ?? null);
    }

    /**
     * @param  list<array<string, mixed>>  $graph  `[]` until 19.1
     */
    public function jsonLd(array $graph): self
    {
        $this->jsonLd = $graph;

        return $this;
    }

    /**
     * @return array{
     *     title: string,
     *     description: string|null,
     *     canonical: string|null,
     *     robots: string,
     *     og: array{type: string, title: string, description: string|null, url: string, image: string|null, image_alt: string|null, site_name: string, locale: string},
     *     twitter_card: string,
     *     json_ld: list<array<string, mixed>>,
     *     preload: list<ImagePreload>
     * }
     */
    public function toArray(Request $request): array
    {
        $siteName = (string) DemoContent::settings('general')['site_name'];
        $indexable = $this->robots === self::ROBOTS_INDEX;

        // A noindex page has no canonical (SEO-TEC-004) unless it names its
        // closest indexable page (filter URLs, SEO-TEC-021). The environment-wide
        // noindex below changes only the robots value, so local output matches production.
        $canonical = $this->canonicalSet ? $this->canonical : ($indexable ? self::cleanUrl($request) : null);
        $image = $this->image ?? DemoMedia::ogImage((string) DemoContent::settings('general')['default_og_image']);

        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $canonical,
            'robots' => self::siteNoindex() ? self::ROBOTS_NONE : $this->robots,
            'og' => [
                'type' => $this->ogType,
                'title' => $this->title,
                'description' => $this->description,
                'url' => $canonical ?? self::cleanUrl($request),
                'image' => $image,
                'image_alt' => $image === null ? null : ($this->imageAlt ?? $siteName),
                'site_name' => $siteName,
                'locale' => 'en_US',
            ],
            'twitter_card' => $image === null ? 'summary' : 'summary_large_image',
            'json_ld' => $this->jsonLd,
            'preload' => $this->preloads(),
        ];
    }

    /**
     * Settings → SEO "noindex whole site", forced on outside production (SEO-TEC-010).
     */
    public static function siteNoindex(): bool
    {
        return ! app()->isProduction() || (bool) DemoContent::settings('seo')['noindex'];
    }

    /**
     * A root-relative path (and query) on the configured domain.
     */
    public static function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * Absolute clean URL on the configured domain: no query except `page` ≥ 2
     * (SEO-TEC-004/005). NormalizeUrl has already fixed slashes and case.
     */
    public static function cleanUrl(Request $request): string
    {
        $url = rtrim((string) config('app.url'), '/').'/'.ltrim($request->path(), '/');
        $page = $request->query('page');

        if (is_string($page) && ctype_digit($page) && (int) $page > 1) {
            $url .= '?page='.(int) $page;
        }

        return $url;
    }

    /**
     * One preload, or two split at 768 px when a mobile crop exists (HERO-030/031).
     *
     * @return list<ImagePreload>
     */
    private function preloads(): array
    {
        if ($this->lcpImage === null) {
            return [];
        }

        ['image' => $image, 'mobile' => $mobile, 'sizes' => $sizes] = $this->lcpImage;

        if ($mobile === null) {
            return [self::preload($image, $sizes, null)];
        }

        return [
            self::preload($mobile, $sizes, '(max-width: 767px)'),
            self::preload($image, $sizes, '(min-width: 768px)'),
        ];
    }

    /**
     * @param  array<string, mixed>  $image  MediaImage
     * @return ImagePreload
     */
    private static function preload(array $image, string $sizes, ?string $media): array
    {
        /** @var array{avif: string|null, webp: string} $sources */
        $sources = $image['sources'];
        $avif = $sources['avif'];

        return [
            'srcset' => $avif ?? $sources['webp'],
            'sizes' => $sizes,
            'type' => $avif === null ? 'image/webp' : 'image/avif',
            'media' => $media,
        ];
    }

    private static function clean(mixed $text): ?string
    {
        $text = is_string($text) ? trim($text) : '';

        return $text === '' ? null : $text;
    }
}
