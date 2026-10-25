<?php

namespace App\Support\Demo;

/**
 * Part A content source (DEC-29): site settings, menus and page copy shaped
 * like the Part B API Resources (ARC-002, resources/js/types/site.ts).
 * Settings move to the database in 12.1, menus in 12.2; deleted in 16.4.
 * Contact details are the spec placeholders (DEC-17), not client data.
 *
 * @phpstan-type MenuItem array{
 *     id: int,
 *     label: string,
 *     url: string,
 *     new_tab: bool,
 *     icon: string|null,
 *     description: string|null,
 *     children: list<array<string, mixed>>,
 *     mega: list<array<string, mixed>>|null
 * }
 * @phpstan-type DemoPage array{title: string, intro: string, description: string}
 */
final class DemoContent
{
    /**
     * Settings groups with the key names of spec/cms.md §8.2.
     *
     * @var array<string, array<string, mixed>>
     */
    private const array SETTINGS = [
        'general' => [
            'site_name' => 'Gemcon City',
            'tagline' => 'Homes and workplaces built to last',
            'logo_light' => '/images/brand/logo-light.svg',
            'logo_dark' => '/images/brand/logo-dark.svg',
            'logo_mark' => '/images/brand/logo-mark.svg',
            'default_og_image' => 'hero/hero-01',
            'parent_company_name' => 'Gemcon Group',
            'parent_company_url' => null,
        ],
        'contact' => [
            'hotline' => '+8809600000000',
            'phones' => ['+8809600000000', '+8801700000000'],
            'emails' => ['info@gemconcity.com', 'sales@gemconcity.com'],
            'whatsapp_number' => '+8801700000000',
            'office_hours_text' => 'Saturday–Thursday, 9:30 am – 6:30 pm',
        ],
        'social' => [
            'facebook' => 'https://www.facebook.com/gemconcity',
            'instagram' => 'https://www.instagram.com/gemconcity',
            'linkedin' => 'https://www.linkedin.com/company/gemconcity',
            'youtube' => 'https://www.youtube.com/@gemconcity',
            'x' => null,
        ],
        'header' => [
            'sticky' => true,
            'transparent_on_hero' => true,
            'cta_label' => 'Enquire',
            'cta_link' => '/contact',
            'show_shortlist' => true,
            'show_phone' => true,
        ],
        'footer' => [
            'about_text' => 'Gemcon City Limited develops apartments, commercial spaces and land projects across Bangladesh, with clear titles, honest timelines and after-sales care.',
            'newsletter_enabled' => true,
            'copyright_text' => '© {year} Gemcon City Limited. All rights reserved.',
            'show_parent_company' => true,
        ],
        'seo' => [
            'title_template' => '{title} | Gemcon City',
            'home_title' => 'Gemcon City Limited – Real Estate Developer in Bangladesh',
            'home_description' => 'Apartments, commercial spaces and land projects by Gemcon City Limited in Dhaka and across Bangladesh. Explore ongoing and upcoming projects and book a site visit.',
            'noindex' => false,
            'gsc_verification' => null,
            'bing_verification' => null,
        ],
        'motion' => [
            'animations_enabled' => true,
            'smooth_scroll_enabled' => true,
            'hero_video_mobile' => false,
        ],
    ];

    /**
     * Stub pages until their phases build them (6.3 builder pages).
     *
     * @var array<string, DemoPage>
     */
    private const array PAGES = [
        'home' => [
            'title' => 'Gemcon City',
            'intro' => 'Website foundation — the home page arrives in phase 2.',
            'description' => '',
        ],
        'about' => [
            'title' => 'About us',
            'intro' => 'Our story, values and the people behind every project. This page is built in phase 6.',
            'description' => 'Gemcon City Limited is a Bangladeshi real estate developer, part of Gemcon Group, building apartments, commercial spaces and land projects since its founding.',
        ],
    ];

    private static int $menuItemId = 0;

    /**
     * @return array<string, mixed>
     */
    public static function settings(string $group): array
    {
        return self::SETTINGS[$group] ?? [];
    }

    /**
     * The `site` shared prop (SiteInfo, ARC-003).
     *
     * @return array<string, mixed>
     */
    public static function site(): array
    {
        $general = self::settings('general');
        $contact = self::settings('contact');
        $header = self::settings('header');
        $footer = self::settings('footer');

        return [
            'name' => $general['site_name'],
            'tagline' => $general['tagline'],
            'logos' => [
                'light' => asset($general['logo_light']),
                'dark' => asset($general['logo_dark']),
                'mark' => asset($general['logo_mark']),
            ],
            'contact' => [
                'hotline' => $contact['hotline'],
                'phones' => $contact['phones'],
                'emails' => $contact['emails'],
                'whatsapp' => $contact['whatsapp_number'],
                'office_hours' => $contact['office_hours_text'],
            ],
            'social' => array_filter(self::settings('social')),
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
                'copyright' => str_replace('{year}', now()->format('Y'), $footer['copyright_text']),
                'show_parent_company' => $footer['show_parent_company'],
            ],
        ];
    }

    /**
     * `ui.motion` (Settings → Motion, MOT-R03).
     *
     * @return array{animations: bool, smooth_scroll: bool, hero_video_mobile: bool}
     */
    public static function motion(): array
    {
        $motion = self::settings('motion');

        return [
            'animations' => (bool) $motion['animations_enabled'],
            'smooth_scroll' => (bool) $motion['smooth_scroll_enabled'],
            'hero_video_mobile' => (bool) $motion['hero_video_mobile'],
        ];
    }

    /**
     * Stub page copy (title, intro, meta description).
     *
     * @return DemoPage
     */
    public static function page(string $key): array
    {
        return self::PAGES[$key];
    }

    /**
     * The `menus` shared prop, keyed by CMS-NAV-001 menu key. URLs are
     * root-relative paths of the sitemap (spec/pages.md §5.1); the pages
     * behind them arrive in later phases.
     *
     * @return array<string, list<MenuItem>>
     */
    public static function menus(): array
    {
        self::$menuItemId = 0;
        $item = self::menuItem(...);

        $segments = [
            $item('Residential', '/projects/type/residential', description: 'Apartments for families'),
            $item('Commercial', '/projects/type/commercial', description: 'Offices and retail spaces'),
            $item('Mixed-use', '/projects/type/mixed-use', description: 'Homes above shops and offices'),
            $item('Land', '/projects/type/land', description: 'Plots with clear titles'),
        ];

        $statuses = [
            $item('Ongoing', '/projects/status/ongoing'),
            $item('Upcoming', '/projects/status/upcoming'),
            $item('Ready', '/projects/status/ready'),
            $item('Handed over', '/projects/status/handed-over'),
            $item('All projects', '/projects'),
        ];

        $locations = array_map(
            fn (array $area): array => $item($area[0], '/locations/'.$area[1]),
            [
                ['Bashundhara R/A', 'bashundhara-ra'],
                ['Gulshan', 'gulshan'],
                ['Banani', 'banani'],
                ['Baridhara', 'baridhara'],
                ['Dhanmondi', 'dhanmondi'],
                ['Uttara', 'uttara'],
                ['Mirpur', 'mirpur'],
                ['Mohammadpur', 'mohammadpur'],
            ],
        );

        // CMS-NAV-003 `auto:featured_projects` is added in 1.1 with the demo project cards.
        // Facet URL slugs `mixed-use` / `handed-over` (enum values use underscores): awaiting owner confirmation.
        $header = [
            $item('Projects', '/projects', mega: [
                ['kind' => 'segments', 'heading' => 'By type', 'items' => $segments],
                ['kind' => 'links', 'heading' => 'By status', 'items' => $statuses],
                ['kind' => 'locations', 'heading' => 'By location', 'items' => $locations],
            ]),
            $item('Locations', '/locations'),
            $item('Landowners', '/landowner'),
            $item('NRB', '/nrb'),
            $item('About', '/about', children: [
                $item('About us', '/about'),
                $item('Leadership', '/about/leadership'),
                $item('Services', '/services'),
                $item('Careers', '/careers'),
            ]),
            $item('News', '/news'),
            $item('Contact', '/contact'),
        ];

        return [
            'header' => $header,
            'footer_1' => [
                $item('All projects', '/projects'),
                $item('Residential', '/projects/type/residential'),
                $item('Commercial', '/projects/type/commercial'),
                $item('Land', '/projects/type/land'),
            ],
            'footer_2' => [
                $item('About us', '/about'),
                $item('Leadership', '/about/leadership'),
                $item('Services', '/services'),
                $item('Careers', '/careers'),
                $item('News', '/news'),
            ],
            'footer_3' => [
                $item('Landowners', '/landowner'),
                $item('NRB buyers', '/nrb'),
                $item('FAQ', '/faq'),
                $item('Contact', '/contact'),
            ],
            'footer_4' => [
                $item('Shortlist', '/shortlist'),
                $item('Compare', '/compare'),
                $item('Search', '/search'),
            ],
            'legal' => [
                $item('Privacy policy', '/privacy-policy'),
                $item('Terms', '/terms'),
            ],
            'mobile_extra' => [
                $item('Book a site visit', '/contact'),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @param  list<array<string, mixed>>|null  $mega
     * @return MenuItem
     */
    private static function menuItem(string $label, string $url, ?string $description = null, array $children = [], ?array $mega = null): array
    {
        return [
            'id' => ++self::$menuItemId,
            'label' => $label,
            'url' => $url,
            'new_tab' => false,
            'icon' => null,
            'description' => $description,
            'children' => $children,
            'mega' => $mega,
        ];
    }
}
