<?php

namespace App\Support\Settings;

use App\Models\MediaAsset;
use App\Support\Demo\DemoMedia;

/**
 * The public shared props that come from Settings (ARC-003): `site`, `ui.motion`, `ui.cookies`,
 * `ui.analytics`, the error-page copy and the brand images. Part A built these from DemoContent
 * (DEC-29); the shapes are unchanged, only the source is now the database with registry defaults.
 */
final class SharedSettings
{
    /** The built-in brand files, used until the client chooses their own in Settings → General. */
    private const array BRAND_DEFAULTS = [
        'logo_light' => '/images/brand/logo-light.svg',
        'logo_dark' => '/images/brand/logo-dark.svg',
        'logo_mark' => '/images/brand/logo-mark.svg',
    ];

    /** @var array<int, MediaAsset>|null */
    private static ?array $brand = null;

    /**
     * The `site` shared prop (SiteInfo, ARC-003).
     *
     * @return array<string, mixed>
     */
    public static function site(): array
    {
        $general = Settings::group('general');
        $contact = Settings::group('contact');
        $header = Settings::group('header');
        $footer = Settings::group('footer');

        return [
            'name' => $general['site_name'],
            'tagline' => $general['tagline'],
            'logos' => [
                'light' => self::brandUrl('logo_light'),
                'dark' => self::brandUrl('logo_dark'),
                'mark' => self::brandUrl('logo_mark'),
            ],
            'contact' => [
                'hotline' => $contact['hotline'],
                'phones' => $contact['phones'],
                'emails' => $contact['emails'],
                'whatsapp' => $contact['whatsapp_number'],
                'office_hours' => $contact['office_hours_text'],
            ],
            'social' => array_filter(Settings::group('social')),
            'parent_company' => [
                'name' => $general['parent_company_name'],
                'url' => $general['parent_company_url'],
            ],
            'header' => [
                'sticky' => $header['sticky'],
                'transparent_on_hero' => $header['transparent_on_hero'],
                'cta' => ['label' => $header['cta_label'], 'url' => $header['cta_link']],
                'show_shortlist' => $header['show_shortlist'],
                'show_phone' => $header['show_phone'],
            ],
            'footer' => [
                'about' => $footer['about_text'],
                'newsletter' => $footer['newsletter_enabled'],
                'copyright' => str_replace('{year}', now()->format('Y'), (string) $footer['copyright_text']),
                'show_parent_company' => $footer['show_parent_company'],
                'newsletter_title' => $footer['newsletter_title'],
                'newsletter_text' => $footer['newsletter_text'],
                'newsletter_success_title' => $footer['newsletter_success_title'],
                'newsletter_success_text' => $footer['newsletter_success_text'],
            ],
        ];
    }

    /**
     * PG-26 copy for one status (Settings → Error pages / Maintenance, DEC-41).
     *
     * @return array{title: string, message: string, popular_heading: string}
     */
    public static function errorPage(int $status): array
    {
        $errors = Settings::group('errors');
        $message = $status === 503 ? Settings::get('maintenance', 'message') : ($errors[$status.'_message'] ?? '');

        return [
            'title' => (string) ($errors[$status.'_title'] ?? ''),
            'message' => (string) $message,
            'popular_heading' => (string) $errors['popular_heading'],
        ];
    }

    /**
     * `ui.motion` (Settings → Motion, MOT-R03).
     *
     * @return array{animations: bool, smooth_scroll: bool, hero_video_mobile: bool}
     */
    public static function motion(): array
    {
        $motion = Settings::group('motion');

        return [
            'animations' => (bool) $motion['animations_enabled'],
            'smooth_scroll' => (bool) $motion['smooth_scroll_enabled'],
            'hero_video_mobile' => (bool) $motion['hero_video_mobile'],
        ];
    }

    /**
     * `ui.cookies` (Settings → Legal & cookies, CMP-COOKIE-01).
     *
     * @return array{enabled: bool, title: string, text: string, policy_url: string, version: int, categories: list<array{key: string, label: string, description: string}>}
     */
    public static function cookies(): array
    {
        $legal = Settings::group('legal');

        return [
            'enabled' => (bool) $legal['cookie_banner_enabled'],
            'title' => (string) $legal['banner_title'],
            'text' => (string) $legal['banner_text'],
            'policy_url' => (string) $legal['policy_link'],
            'version' => (int) $legal['consent_version'],
            'categories' => array_map(
                fn (string $key): array => [
                    'key' => $key,
                    'label' => (string) $legal["category_{$key}"]['label'],
                    'description' => (string) $legal["category_{$key}"]['description'],
                ],
                ['necessary', 'analytics', 'marketing'],
            ),
        ];
    }

    /**
     * `ui.analytics` (Settings → Analytics, SEO-ANA-001): public container / measurement IDs only.
     * Both null means nothing ever loads. When both are set GA4 loads through GTM, so only the GTM
     * ID is sent (cms.md §8.2).
     *
     * @return array{gtm_id: string|null, ga4_id: string|null}
     */
    public static function analytics(): array
    {
        $analytics = Settings::group('analytics');
        $gtm = $analytics['gtm_id'] ?? null;
        $ga4 = $analytics['ga4_id'] ?? null;
        $gtm = is_string($gtm) && preg_match('/^GTM-[A-Z0-9]{6,8}$/', $gtm) === 1 ? $gtm : null;
        $ga4 = is_string($ga4) && preg_match('/^G-[A-Z0-9]{8,12}$/', $ga4) === 1 ? $ga4 : null;

        return ['gtm_id' => $gtm, 'ga4_id' => $gtm === null ? $ga4 : null];
    }

    /**
     * Link tags for the chosen favicon, or null while the built-in icons are used.
     *
     * @return array{href: string, type: string}|null
     */
    public static function favicon(): ?array
    {
        $asset = self::brandAsset('favicon');
        $url = $asset === null ? null : $asset->url();

        if ($asset === null || $url === null) {
            return null;
        }

        return ['href' => $url, 'type' => (string) ($asset->file()?->mime_type ?: 'image/png')];
    }

    /**
     * The share image of pages without their own (SEO-ONP): the chosen library image's 1200×630
     * crop, else the demo photo.
     */
    public static function defaultOgImage(): ?string
    {
        $asset = self::brandAsset('default_og_image');

        return $asset?->ogUrl() ?? $asset?->url() ?? DemoMedia::ogImage('hero/hero-01');
    }

    private static function brandUrl(string $key): string
    {
        return self::brandAsset($key)?->url() ?? asset(self::BRAND_DEFAULTS[$key]);
    }

    private static function brandAsset(string $key): ?MediaAsset
    {
        if (self::$brand === null) {
            $general = Settings::group('general');
            $ids = array_values(array_filter(array_map(
                fn (string $field): ?int => isset($general[$field]) ? (int) $general[$field] : null,
                ['logo_light', 'logo_dark', 'logo_mark', 'favicon', 'default_og_image'],
            )));

            self::$brand = $ids === [] ? [] : MediaAsset::query()->with('media')->whereIn('id', $ids)->get()->keyBy('id')->all();
        }

        $id = Settings::group('general')[$key] ?? null;

        return $id === null ? null : (self::$brand[(int) $id] ?? null);
    }

    /** Forget what this request has read (after a save, in checks). */
    public static function flushMemo(): void
    {
        self::$brand = null;
    }
}
