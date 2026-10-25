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
 *     count: int|null,
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
            // DEC-41: footer copy keys the spec leaves out.
            'newsletter_title' => 'Project updates in your inbox',
            'newsletter_text' => 'New launches, construction progress and offers. No spam, unsubscribe any time.',
            'offices_heading' => 'Offices',
            'locations_heading' => 'Popular areas',
        ],
        'seo' => [
            'title_template' => '{title} | Gemcon City',
            'home_title' => 'Gemcon City Limited – Real Estate Developer in Bangladesh',
            'home_description' => 'Apartments, commercial spaces and land projects by Gemcon City Limited in Dhaka and across Bangladesh. Explore ongoing and upcoming projects and book a site visit.',
            'noindex' => false,
            'gsc_verification' => null,
            'bing_verification' => null,
        ],
        // cms.md §8.2 Search; band labels are HERO-040's defaults.
        'search' => [
            'size_bands' => [
                ['label' => '< 1,200 sft', 'min' => null, 'max' => 1200],
                ['label' => '1,200–1,600 sft', 'min' => 1200, 'max' => 1600],
                ['label' => '1,600–2,000 sft', 'min' => 1600, 'max' => 2000],
                ['label' => '2,000–2,500 sft', 'min' => 2000, 'max' => 2500],
                ['label' => '2,500+ sft', 'min' => 2500, 'max' => null],
            ],
            'price_filter_min_projects' => 5,
            'results_per_page' => 12,
        ],
        'motion' => [
            'animations_enabled' => true,
            'smooth_scroll_enabled' => true,
            'hero_video_mobile' => false,
        ],
        'maintenance' => [
            'message' => 'We are updating the website and will be back shortly. For urgent enquiries please call our hotline.',
        ],
        // PG-26 editable copy (DEC-41): `{status}_title` is the H1, `{status}_message` the text below it.
        'errors' => [
            '404_title' => 'We could not find that page',
            '404_message' => 'The page may have moved or no longer exists. Try a search, or start from one of our popular projects.',
            '410_title' => 'This page is no longer available',
            '410_message' => 'The project, article or job you were looking for has been removed.',
            '419_title' => 'This page has expired',
            '419_message' => 'For your security the form timed out. Please reload the page and try again.',
            '429_title' => 'Too many requests',
            '429_message' => 'Please wait a moment before trying again.',
            '500_title' => 'Something went wrong',
            '500_message' => 'We have been notified and are fixing it. Please try again in a few minutes.',
            '503_title' => 'We will be right back',
            'popular_heading' => 'Popular projects',
        ],
    ];

    /**
     * DM-MENU names, used as footer column headings.
     *
     * @var array<string, string>
     */
    private const array MENU_NAMES = [
        'footer_1' => 'Projects',
        'footer_2' => 'Company',
        'footer_3' => 'Buyers & landowners',
        'footer_4' => 'Tools',
    ];

    /**
     * `auto:locations` and the footer's top 8 areas (CMS-NAV-003,
     * SEO-ONP-005): name, slug, published project count.
     *
     * @var list<array{0: string, 1: string, 2: int}>
     */
    private const array TOP_AREAS = [
        ['Bashundhara R/A', 'bashundhara-ra', 6],
        ['Gulshan', 'gulshan', 5],
        ['Banani', 'banani', 4],
        ['Baridhara', 'baridhara', 3],
        ['Dhanmondi', 'dhanmondi', 3],
        ['Uttara', 'uttara', 2],
        ['Mirpur', 'mirpur', 2],
        ['Mohammadpur', 'mohammadpur', 1],
    ];

    /**
     * Areas outside Dhaka for the search widget's city groups (HERO-040);
     * placeholders like TOP_AREAS: name, slug, published project count, city.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string}>
     */
    private const array OTHER_AREAS = [
        ['Khulshi', 'khulshi', 1, 'Chattogram'],
        ['Panchlaish', 'panchlaish', 1, 'Chattogram'],
    ];

    /**
     * DM-OFF rows (spec/social.md); placeholder addresses until the client's arrive.
     *
     * @var list<array<string, mixed>>
     */
    private const array OFFICES = [
        [
            'name' => 'Head office',
            'address' => 'House 00, Road 00, Gulshan 2',
            'city' => 'Dhaka',
            'postal_code' => '1212',
            'phones' => ['+8809600000000'],
            'email' => 'info@gemconcity.com',
            'hours' => [['days' => ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'opens' => '09:30', 'closes' => '18:30']],
            'latitude' => 23.7925,
            'longitude' => 90.4078,
            'is_head_office' => true,
        ],
        [
            'name' => 'Sales lounge',
            'address' => 'Plot 00, Block A, Bashundhara R/A',
            'city' => 'Dhaka',
            'postal_code' => '1229',
            'phones' => ['+8801700000000'],
            'email' => 'sales@gemconcity.com',
            'hours' => [['days' => ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'opens' => '10:00', 'closes' => '20:00']],
            'latitude' => 23.8193,
            'longitude' => 90.4526,
            'is_head_office' => false,
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
            'intro' => 'The home page blocks below the hero (featured projects, segments, stats, news) arrive in phases 2.2 and 2.3.',
            'description' => '',
        ],
        'about' => [
            'title' => 'About us',
            'intro' => 'Our story, values and the people behind every project. This page is built in phase 6.',
            'description' => 'Gemcon City Limited is a Bangladeshi real estate developer, part of Gemcon Group, building apartments, commercial spaces and land projects since its founding.',
        ],
        'leadership' => [
            'title' => 'Leadership',
            'intro' => 'The board and management team of Gemcon City Limited. This page is built in phase 6.',
            'description' => 'Meet the board and management team leading Gemcon City Limited.',
        ],
    ];

    /**
     * Demo projects for ProjectCardResource rows (CMP-CARD-01); placeholder
     * names until the client's list arrives. Prices of `price_visible: false`
     * rows never leave the server (AC-PROJ-01).
     *
     * @var list<array{slug: string, name: string, segment: string, lifecycle: string, booking_state: string, featured: bool, area: string, image: string, size: array{0: int|null, 1: int|null}, beds: array{0: int|null, 1: int|null}, baths: array{0: int|null, 1: int|null}, katha: int|float|null, price: int|null, price_visible: bool}>
     */
    private const array PROJECTS = [
        ['slug' => 'gemcon-lakeview', 'name' => 'Gemcon Lakeview', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'Bashundhara R/A', 'image' => 'projects/projects-01', 'size' => [1650, 2100], 'beds' => [3, 4], 'baths' => [3, 4], 'katha' => null, 'price' => 14_500_000, 'price_visible' => true],
        ['slug' => 'gemcon-orchid-heights', 'name' => 'Gemcon Orchid Heights', 'segment' => 'residential', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'Gulshan', 'image' => 'projects/projects-02', 'size' => [2400, 3200], 'beds' => [4, 4], 'baths' => [4, 5], 'katha' => null, 'price' => 42_000_000, 'price_visible' => false],
        ['slug' => 'gemcon-trade-centre', 'name' => 'Gemcon Trade Centre', 'segment' => 'commercial', 'lifecycle' => 'ongoing', 'booking_state' => 'limited_units', 'featured' => true, 'area' => 'Banani', 'image' => 'projects/projects-03', 'size' => [850, 5200], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => 9_500_000, 'price_visible' => true],
        ['slug' => 'gemcon-greenfield', 'name' => 'Gemcon Greenfield', 'segment' => 'residential', 'lifecycle' => 'ready', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'Uttara', 'image' => 'projects/projects-04', 'size' => [1250, 1450], 'beds' => [3, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 8_500_000, 'price_visible' => true],
        ['slug' => 'gemcon-riverside-plots', 'name' => 'Gemcon Riverside Plots', 'segment' => 'land', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'Mirpur', 'image' => 'projects/projects-05', 'size' => [null, null], 'beds' => [null, null], 'baths' => [null, null], 'katha' => 5, 'price' => 12_500_000, 'price_visible' => true],
        ['slug' => 'gemcon-crescent', 'name' => 'Gemcon Crescent', 'segment' => 'mixed_use', 'lifecycle' => 'upcoming', 'booking_state' => 'none', 'featured' => false, 'area' => 'Dhanmondi', 'image' => 'projects/projects-06', 'size' => [1100, 2600], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => null, 'price_visible' => false],
        ['slug' => 'gemcon-palm-court', 'name' => 'Gemcon Palm Court', 'segment' => 'residential', 'lifecycle' => 'handed_over', 'booking_state' => 'sold_out', 'featured' => false, 'area' => 'Baridhara', 'image' => 'projects/projects-07', 'size' => [2800, 2800], 'beds' => [4, 4], 'baths' => [5, 5], 'katha' => null, 'price' => null, 'price_visible' => false],
        ['slug' => 'gemcon-maple-residency', 'name' => 'Gemcon Maple Residency', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'Mohammadpur', 'image' => 'projects/projects-08', 'size' => [1050, 1350], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 6_800_000, 'price_visible' => true],
    ];

    private const array SEGMENT_LABELS = ['residential' => 'Residential', 'commercial' => 'Commercial', 'mixed_use' => 'Mixed-use', 'land' => 'Land'];

    private const array LIFECYCLE_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'ready' => 'Ready', 'handed_over' => 'Handed over'];

    private const array BOOKING_LABELS = ['booking_open' => 'Booking open', 'limited_units' => 'Limited units', 'sold_out' => 'Sold out'];

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
                'newsletter_title' => $footer['newsletter_title'],
                'newsletter_text' => $footer['newsletter_text'],
            ],
        ];
    }

    /**
     * The `footer` shared prop (CMP-FOOT-01): headings of the `footer_1…4`
     * menus, offices and the top 8 areas (SEO-ONP-005).
     *
     * @return array<string, mixed>
     */
    public static function footer(): array
    {
        $footer = self::settings('footer');
        self::$menuItemId = 1000;
        $item = self::menuItem(...);

        return [
            'columns' => array_map(
                fn (string $key): array => ['menu' => $key, 'heading' => self::MENU_NAMES[$key]],
                array_keys(self::MENU_NAMES),
            ),
            'offices_heading' => $footer['offices_heading'],
            'offices' => self::offices(),
            'locations_heading' => $footer['locations_heading'],
            'locations' => array_map(
                fn (array $area): array => $item($area[0], '/locations/'.$area[1], count: $area[2]),
                self::TOP_AREAS,
            ),
        ];
    }

    /**
     * Offices (OfficeResource shape, DM-OFF), head office first.
     *
     * @return list<array<string, mixed>>
     */
    public static function offices(): array
    {
        return array_map(
            fn (array $office, int $index): array => ['id' => $index + 1, ...$office],
            self::OFFICES,
            array_keys(self::OFFICES),
        );
    }

    /**
     * PG-26 copy for one status (Settings → Errors / Maintenance, DEC-41).
     *
     * @return array{title: string, message: string, popular_heading: string}
     */
    public static function errorPage(int $status): array
    {
        $errors = self::settings('errors');
        $message = $status === 503 ? self::settings('maintenance')['message'] : $errors[$status.'_message'];

        return [
            'title' => (string) $errors[$status.'_title'],
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
     * The home page blocks (DM-BLOCK shape, types/blocks.ts). Only the hero
     * exists so far; 2.2/2.3 add the rest of LAY-HOME. `$variant` switches
     * the hero for the local previews at /styleguide/hero/{variant}.
     *
     * @return list<array<string, mixed>>
     */
    public static function homeBlocks(string $variant = 'image'): array
    {
        return [self::block(1, 'hero', self::hero($variant))];
    }

    /**
     * CMP-SEARCH-01 options (ProjectSearchOptions in types/search.ts):
     * values are FR-SRCH-002 query values, `url` the clean landing page a
     * single facet goes to (HERO-041, FR-SRCH-008).
     *
     * @return array<string, mixed>
     */
    public static function projectSearch(): array
    {
        $option = fn (string $value, string $label, ?string $url): array => ['value' => $value, 'label' => $label, 'url' => $url];
        // Facet URL slugs use hyphens (`handed-over`); awaiting owner confirmation (see menus()).
        $slug = fn (string $value): string => str_replace('_', '-', $value);

        $cities = [];
        $areas = [...array_map(fn (array $area): array => [...$area, 'Dhaka'], self::TOP_AREAS), ...self::OTHER_AREAS];

        foreach ($areas as [$name, $areaSlug, , $city]) {
            $cities[$city][] = $option($areaSlug, $name, '/locations/'.$areaSlug);
        }

        return [
            'action' => '/projects',
            'segments' => array_map(
                fn (string $value): array => $option($value, self::SEGMENT_LABELS[$value], '/projects/type/'.$slug($value)),
                ['residential', 'commercial', 'land'],
            ),
            'statuses' => array_map(
                fn (string $value): array => $option($value, self::LIFECYCLE_LABELS[$value], '/projects/status/'.$slug($value)),
                array_keys(self::LIFECYCLE_LABELS),
            ),
            'cities' => array_map(
                fn (string $city, array $cityAreas): array => ['city' => $city, 'areas' => $cityAreas],
                array_keys($cities),
                array_values($cities),
            ),
            // DEC-42: one `size` value "{min}-{max}" so the no-JS form can submit it.
            'sizes' => array_map(
                fn (array $band): array => [
                    'value' => $band['min'].'-'.$band['max'],
                    'label' => $band['label'],
                    'min' => $band['min'],
                    'max' => $band['max'],
                ],
                self::settings('search')['size_bands'],
            ),
            'beds' => array_map(
                fn (int $beds): array => $option((string) $beds, $beds === 5 ? '5+ beds' : $beds.' '.($beds === 1 ? 'bed' : 'beds'), null),
                range(1, 5),
            ),
        ];
    }

    /**
     * Project cards (ProjectCard in types/site.ts), optionally featured only.
     *
     * @return list<array<string, mixed>>
     */
    public static function projectCards(bool $featuredOnly = false): array
    {
        $cards = [];

        foreach (self::PROJECTS as $index => $row) {
            if ($featuredOnly && ! $row['featured']) {
                continue;
            }

            $cards[] = [
                'id' => $index + 1,
                'slug' => $row['slug'],
                'url' => '/projects/'.$row['slug'],
                'name' => $row['name'],
                'segment' => $row['segment'],
                'segment_label' => self::SEGMENT_LABELS[$row['segment']],
                'lifecycle' => $row['lifecycle'],
                'lifecycle_label' => self::LIFECYCLE_LABELS[$row['lifecycle']],
                'booking_state' => $row['booking_state'],
                'booking_label' => self::BOOKING_LABELS[$row['booking_state']] ?? null,
                'is_featured' => $row['featured'],
                'location' => ['area' => $row['area'], 'city' => 'Dhaka'],
                'cover' => DemoMedia::image($row['image']),
                'size_min_sft' => $row['size'][0],
                'size_max_sft' => $row['size'][1],
                'beds_min' => $row['beds'][0],
                'beds_max' => $row['beds'][1],
                'baths_min' => $row['baths'][0],
                'baths_max' => $row['baths'][1],
                'land_katha' => $row['katha'],
                'price_from_bdt' => $row['price_visible'] ? $row['price'] : null,
                'price_note' => $row['price_visible'] ? null : 'Price on request',
            ];
        }

        return $cards;
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
            $item('Residential', '/projects/type/residential', description: 'Apartments for families', icon: 'house'),
            $item('Commercial', '/projects/type/commercial', description: 'Offices and retail spaces', icon: 'building'),
            $item('Mixed-use', '/projects/type/mixed-use', description: 'Homes above shops and offices', icon: 'layers'),
            $item('Land', '/projects/type/land', description: 'Plots with clear titles', icon: 'land-plot'),
        ];

        $statuses = [
            $item('Ongoing', '/projects/status/ongoing'),
            $item('Upcoming', '/projects/status/upcoming'),
            $item('Ready', '/projects/status/ready'),
            $item('Handed over', '/projects/status/handed-over'),
            $item('All projects', '/projects'),
        ];

        // `auto:locations`: top 8 areas by project count (CMS-NAV-003).
        $locations = array_map(
            fn (array $area): array => $item($area[0], '/locations/'.$area[1], count: $area[2]),
            self::TOP_AREAS,
        );

        // Contact is not a header item: the Enquire button links there (DEC-40).
        // Facet URL slugs `mixed-use` / `handed-over` (enum values use underscores): awaiting owner confirmation.
        $header = [
            $item('Projects', '/projects', mega: [
                ['kind' => 'segments', 'heading' => 'By type', 'items' => $segments],
                ['kind' => 'links', 'heading' => 'By status', 'items' => $statuses],
                ['kind' => 'locations', 'heading' => 'By location', 'items' => $locations],
                ['kind' => 'featured_projects', 'heading' => 'Featured', 'projects' => array_slice(self::projectCards(featuredOnly: true), 0, 3)],
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
                $item('Contact', '/contact'),
                $item('Book a site visit', '/contact'),
            ],
        ];
    }

    /**
     * Hero block data (spec/hero.md §6.2–6.4, HeroBlockData in types/blocks.ts).
     *
     * @return array<string, mixed>
     */
    private static function hero(string $variant): array
    {
        $hero = [
            'mode' => 'single',
            'media_type' => 'image',
            'image' => DemoMedia::image('hero/hero-01'),
            'mobile_image' => DemoMedia::image('hero/hero-01-mobile'),
            'video' => null,
            'slides' => [],
            'autoplay' => false,
            'eyebrow' => 'Gemcon City Limited',
            'headline' => 'Homes and workplaces built to last',
            'subheadline' => 'Apartments, commercial spaces and land projects in Dhaka with clear titles, honest timelines and after-sales care.',
            'primary_cta' => ['label' => 'Explore projects', 'url' => '/projects', 'new_tab' => false],
            'secondary_cta' => ['label' => 'Book a site visit', 'url' => '/contact', 'new_tab' => false],
            'overlay_opacity' => 45,
            'overlay_style' => 'gradient-bottom',
            'text_align' => 'left',
            'show_search' => true,
            'height' => 'full',
        ];

        $hero = match ($variant) {
            // The poster follows HERO-010…014 and stays the LCP element (HERO-021).
            'video' => [...$hero, 'media_type' => 'video', 'video' => DemoMedia::video('video/hero')],
            // Autoplay is on here only so the pause control can be tested (HERO-003 default: off).
            'slider' => [...$hero, 'mode' => 'slider', 'autoplay' => true, 'slides' => [
                ['image' => DemoMedia::image('hero/hero-01'), 'mobile_image' => DemoMedia::image('hero/hero-01-mobile')],
                ['image' => DemoMedia::image('hero/hero-02'), 'mobile_image' => null],
                ['image' => DemoMedia::image('hero/hero-03'), 'mobile_image' => null],
            ]],
            'centered' => [
                ...$hero,
                'text_align' => 'center',
                'overlay_style' => 'solid',
                'overlay_opacity' => 55,
                'height' => 'large',
                'show_search' => false,
                'secondary_cta' => null,
            ],
            default => $hero,
        };

        return [...$hero, 'search' => $hero['show_search'] ? self::projectSearch() : null];
    }

    /**
     * DM-BLOCK envelope (CMS-PB-001).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function block(int $id, string $type, array $data): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'anchor_id' => null,
            'background' => 'default',
            'spacing' => 'none',
            'animate' => true,
            'data' => $data,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @param  list<array<string, mixed>>|null  $mega
     * @return MenuItem
     */
    private static function menuItem(string $label, string $url, ?string $description = null, ?string $icon = null, ?int $count = null, array $children = [], ?array $mega = null): array
    {
        return [
            'id' => ++self::$menuItemId,
            'label' => $label,
            'url' => $url,
            'new_tab' => false,
            'icon' => $icon,
            'description' => $description,
            'count' => $count,
            'children' => $children,
            'mega' => $mega,
        ];
    }
}
