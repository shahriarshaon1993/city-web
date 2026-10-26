<?php

namespace App\Support\Demo;

use App\Support\Search\ProjectFilters;

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

    /** Mega menu `auto:segments` descriptions, reused by the segment tiles. */
    private const array SEGMENT_DESCRIPTIONS = [
        'residential' => 'Apartments for families',
        'commercial' => 'Offices and retail spaces',
        'mixed_use' => 'Homes above shops and offices',
        'land' => 'Plots with clear titles',
    ];

    /** DM-PROG stages (spec/projects.md) with their labels. */
    private const array PROGRESS_STAGES = ['foundation' => 'Foundation', 'structure' => 'Structure', 'finishing' => 'Finishing', 'complete' => 'Complete'];

    /**
     * Latest DM-PROG update of the projects in the home progress_highlight
     * block; placeholder figures until the client's reports arrive.
     *
     * @var list<array{project: string, percent: int, stage: string, reported_on: string, note: string, photo: string}>
     */
    private const array PROGRESS = [
        ['project' => 'gemcon-trade-centre', 'percent' => 81, 'stage' => 'finishing', 'reported_on' => '2026-10-01', 'note' => 'Curtain wall glazing is complete up to level 14; lift installation has started in the east core.', 'photo' => 'progress/progress-01'],
        ['project' => 'gemcon-lakeview', 'percent' => 64, 'stage' => 'structure', 'reported_on' => '2026-09-01', 'note' => 'Roof slab of Tower B is cast. Brickwork and electrical conduits are under way on levels 1–9.', 'photo' => 'progress/progress-02'],
        ['project' => 'gemcon-maple-residency', 'percent' => 18, 'stage' => 'foundation', 'reported_on' => '2026-08-01', 'note' => 'Piling is finished and the raft foundation is being poured in three sections.', 'photo' => 'progress/progress-03'],
    ];

    /**
     * DM-TEST rows (FR-SOC-001): plain-text quotes, no rating. Fictional
     * placeholders; no photos until the client supplies them (DEC-47).
     *
     * @var list<array{quote: string, person_name: string, designation: string|null, project: string|null, is_nrb: bool}>
     */
    private const array TESTIMONIALS = [
        ['quote' => 'We booked from Toronto without ever visiting the site. The monthly progress photos and a sales team that answered every email at odd hours made the decision easy. The flat was handed over on the promised date.', 'person_name' => 'Farhana Rahman', 'designation' => 'Software engineer, Toronto', 'project' => 'gemcon-greenfield', 'is_nrb' => true],
        ['quote' => 'Clear papers, a RAJUK-approved plan and no surprises in the final bill. That is rare in this market.', 'person_name' => 'Mahmudul Hasan', 'designation' => 'Apartment owner', 'project' => 'gemcon-palm-court', 'is_nrb' => false],
        ['quote' => 'We gave our family land in a joint venture. Gemcon explained every clause, kept us updated at each stage and delivered our share of flats exactly as agreed.', 'person_name' => 'Nasreen Akter', 'designation' => 'Landowner', 'project' => null, 'is_nrb' => false],
        ['quote' => 'Our office floor at the Trade Centre was ready for fit-out a month early. The building management team is quick and professional.', 'person_name' => 'Tanvir Ahmed', 'designation' => 'Managing director, logistics firm', 'project' => 'gemcon-trade-centre', 'is_nrb' => false],
        ['quote' => 'After-sales service is what sold us on a second apartment. Small snags after handover were fixed within a week, without us having to chase anyone.', 'person_name' => 'Shirin Sultana', 'designation' => 'Homeowner since 2019', 'project' => 'gemcon-greenfield', 'is_nrb' => false],
        ['quote' => 'Buying from Dubai felt risky until our first video site visit. The engineer walked us through the structure floor by floor and answered every question.', 'person_name' => 'Kamrul Islam', 'designation' => 'Engineer, Dubai', 'project' => 'gemcon-lakeview', 'is_nrb' => true],
    ];

    /**
     * DM-PART rows (FR-SOC-002); the logos are the fictional wordmarks of
     * `demo:images --placeholders` (DEC-47), in the same order.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const array PARTNERS = [
        ['Tallgrass Bank', 'bank'],
        ['Ironleaf Steel', 'supplier'],
        ['Brightmortar', 'supplier'],
        ['Clearpane Glass', 'supplier'],
        ['Fieldline Consultants', 'consultant'],
        ['Kestrel Finance', 'bank'],
        ['Northcourt Cables', 'supplier'],
        ['Saltmarsh Tiles', 'supplier'],
        ['Orbitwell Lifts', 'supplier'],
        ['Redbrick Group', 'group_company'],
    ];

    /** DM-PCAT defaults (FR-NEWS-002): slug => name. */
    private const array POST_CATEGORIES = ['news' => 'News', 'press-releases' => 'Press releases', 'buying-guides' => 'Buying guides', 'construction-updates' => 'Construction updates', 'events' => 'Events'];

    /**
     * DM-POST rows for news cards (CMP-NEWS-01), newest first; placeholders.
     *
     * @var list<array{slug: string, title: string, excerpt: string, category: string, published_at: string, read_minutes: int, cover: string}>
     */
    private const array POSTS = [
        ['slug' => 'gemcon-trade-centre-tops-out', 'title' => 'Gemcon Trade Centre tops out at 18 floors in Banani', 'excerpt' => 'The final roof slab was cast this month, keeping the commercial tower on track for handover in mid-2027.', 'category' => 'construction-updates', 'published_at' => '2026-10-18', 'read_minutes' => 3, 'cover' => 'news/news-01'],
        ['slug' => 'buying-an-apartment-from-abroad', 'title' => 'Buying an apartment in Dhaka from abroad: a step-by-step guide for NRBs', 'excerpt' => 'Power of attorney, remittance rules and site visits by video. Everything you need before booking from overseas.', 'category' => 'buying-guides', 'published_at' => '2026-10-02', 'read_minutes' => 7, 'cover' => 'news/news-02'],
        ['slug' => 'rehab-fair-2026', 'title' => 'Meet us at the REHAB Fair 2026', 'excerpt' => 'Visit our pavilion for fair-only booking offers on Gemcon Lakeview and Gemcon Maple Residency.', 'category' => 'events', 'published_at' => '2026-09-21', 'read_minutes' => 2, 'cover' => 'news/news-03'],
        ['slug' => 'gemcon-orchid-heights-launch', 'title' => 'Gemcon Orchid Heights launches in Gulshan', 'excerpt' => 'Twenty-four four-bedroom residences on a quiet lakeside plot, with bookings now open.', 'category' => 'press-releases', 'published_at' => '2026-09-05', 'read_minutes' => 4, 'cover' => 'news/news-04'],
        ['slug' => 'understanding-rajuk-approval', 'title' => 'What a RAJUK approval tells you about a project', 'excerpt' => 'How to read an approval letter and which documents to ask for before you sign.', 'category' => 'buying-guides', 'published_at' => '2026-08-17', 'read_minutes' => 6, 'cover' => 'news/news-05'],
        ['slug' => 'handover-gemcon-palm-court', 'title' => 'Keys handed over at Gemcon Palm Court', 'excerpt' => 'Families moved into all 32 apartments of our Baridhara project this summer.', 'category' => 'news', 'published_at' => '2026-07-29', 'read_minutes' => 3, 'cover' => 'news/news-06'],
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
                fn (array $area): array => $item($area['name'], '/locations/'.$area['slug'], count: $area['count']),
                DemoProjects::topAreas(),
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
     * PG-02/PG-03 copy: the listing's own title/intro and the DM-FACET rows
     * (`facets["segment:residential"]`), with an optional FAQ per facet.
     * Placeholder text until 16.4 moves facet pages to the admin.
     *
     * @return array{
     *     listing: array{title: string, intro: string|null, description: string},
     *     facets: array<string, array{title: string, intro: string|null, description: string, faq: list<array{id: int, question: string, answer: string}>}>,
     *     copy: array<string, string>
     * }
     */
    public static function projectListing(): array
    {
        $facet = fn (string $title, string $intro, string $description, array $faq = []): array => [
            'title' => $title,
            'intro' => $intro,
            'description' => $description,
            'faq' => array_map(
                fn (array $item, int $index): array => ['id' => $index + 1, 'question' => $item[0], 'answer' => $item[1]],
                $faq,
                array_keys($faq),
            ),
        ];

        return [
            'listing' => [
                'title' => 'Projects in Bangladesh',
                'intro' => null,
                'description' => 'Browse every Gemcon City project: apartments, commercial spaces and land in Dhaka and Chattogram. Filter by area, size, bedrooms, price and status.',
            ],
            'facets' => [
                'segment:residential' => $facet(
                    'Residential projects in Bangladesh',
                    'Family apartments from two to five bedrooms, built to RAJUK-approved designs with clear titles. Every project lists its sizes, handover date and monthly construction progress, so you can compare homes before you visit the site.',
                    'Apartments for sale in Dhaka and Chattogram by Gemcon City: ongoing, upcoming and ready residential projects with sizes, prices and handover dates.',
                    [
                        ['Can I book an apartment from abroad?', '<p>Yes. Most of our NRB buyers book by video site visit and a power of attorney. Our sales team sends the documents, payment schedule and progress photos by email.</p>'],
                        ['What is included in the apartment price?', '<p>The price covers the apartment, its share of land and a car park where stated. Registration costs and utility connection fees are listed separately in the price sheet.</p>'],
                        ['How do instalments work?', '<p>A booking money payment is followed by instalments linked to construction stages. The schedule for each project is in its brochure.</p>'],
                    ],
                ),
                'segment:commercial' => $facet(
                    'Commercial projects in Bangladesh',
                    'Office floors, retail spaces and showrooms in Banani, Gulshan, Uttara and Chattogram, with flexible floor plates and professional building management.',
                    'Office and retail spaces for sale by Gemcon City in Dhaka and Chattogram, from single suites to full floors.',
                ),
                'segment:mixed_use' => $facet(
                    'Mixed-use projects in Bangladesh',
                    'Homes above shops, markets and offices: everyday errands downstairs and a quiet apartment upstairs.',
                    'Mixed-use developments by Gemcon City combining apartments with retail and office space in Dhaka and Chattogram.',
                ),
                'segment:land' => $facet(
                    'Land projects in Bangladesh',
                    'Residential plots with clear titles, mutation completed and roads, drainage and utilities laid before handover.',
                    'Plots for sale by Gemcon City in Dhaka with clear titles, planned roads and utilities.',
                ),
                'status:upcoming' => $facet(
                    'Upcoming projects in Bangladesh',
                    'Projects that are approved and open for early booking, often at launch prices before construction starts.',
                    'Upcoming Gemcon City projects open for early booking in Dhaka and Chattogram.',
                ),
                'status:ongoing' => $facet(
                    'Ongoing projects in Bangladesh',
                    'Projects under construction, with progress reports every month and handover dates we publish and keep.',
                    'Gemcon City projects under construction in Dhaka and Chattogram, with monthly progress updates and handover dates.',
                    [
                        ['How do I follow construction progress?', '<p>Every ongoing project page shows the latest progress report with photos and the percentage complete. Buyers also receive the report by email each month.</p>'],
                        ['Can I visit the site during construction?', '<p>Yes. Book a site visit and an engineer will walk you through the building with safety equipment.</p>'],
                    ],
                ),
                'status:ready' => $facet(
                    'Ready apartments and spaces in Bangladesh',
                    'Completed projects with units still available: move in or start your fit-out within weeks of booking.',
                    'Ready-to-move apartments and commercial spaces by Gemcon City with units available now.',
                ),
                'status:handed_over' => $facet(
                    'Handed-over projects',
                    'Buildings we have completed and handed over to their owners, a record of the promises we kept.',
                    'Completed and handed-over Gemcon City projects in Dhaka and Chattogram.',
                ),
            ],
            'copy' => [
                'empty_title' => 'No projects match these filters',
                'empty_body' => 'Try fewer filters or a wider size or price range. Here are our newest projects in the meantime.',
                'newest_heading' => 'Newest projects',
                'locations_heading' => 'Explore by area',
                'faq_heading' => 'Frequently asked questions',
                'map_title' => 'Map view is on its way',
                'map_body' => 'The interactive map arrives with the locations pages. The list shows the same projects in the meantime.',
            ],
        ];
    }

    /**
     * The listing pages' closing CTA band (PG-02 "CTA band", CMP-BLK-CTA).
     *
     * @return array<string, mixed>
     */
    public static function projectListingCta(): array
    {
        return self::block(900, 'cta_band', [
            'heading' => 'Not sure which project fits?',
            'text' => 'Tell us your budget, preferred area and timeline, and our sales team will shortlist projects for you.',
            'primary_cta' => ['label' => 'Talk to our team', 'url' => '/contact', 'new_tab' => false],
            'secondary_cta' => ['label' => 'Call the hotline', 'url' => 'tel:'.self::SETTINGS['contact']['hotline'], 'new_tab' => false],
            'style' => 'gradient',
            'image' => null,
        ], background: 'dark', spacing: 'lg');
    }

    /**
     * The home page blocks (DM-BLOCK shape, types/blocks.ts) in LAY-HOME
     * order. Project, testimonial, partner and post lists are resolved here
     * the way the Part B block data loaders will (ARC-002). `$variant` switches the hero for the local previews at
     * /styleguide/hero/{variant}.
     *
     * @return list<array<string, mixed>>
     */
    public static function homeBlocks(string $variant = 'image'): array
    {
        return [
            self::block(1, 'hero', self::hero($variant)),
            self::block(2, 'intro_statement', [
                'eyebrow' => 'About Gemcon City',
                'heading' => 'We build the homes we would want to live in.',
                'body' => '<p>For two decades Gemcon City has delivered apartments, workplaces and plots across Dhaka with clear titles, RAJUK-approved designs and handovers on the dates we promise.</p><p>Every project is engineered for the long run, and our after-sales team stays with you long after the keys change hands.</p>',
                'cta' => ['label' => 'Our story', 'url' => '/about', 'new_tab' => false],
                'image' => DemoMedia::image('interiors/interiors-01'),
            ], spacing: 'lg'),
            self::block(3, 'featured_projects', [
                'heading' => 'Featured projects',
                'source' => 'featured',
                'layout' => 'carousel',
                'projects' => self::projectCards(featuredOnly: true, limit: 6),
                'view_all' => ['label' => 'View all projects', 'url' => '/projects', 'new_tab' => false],
            ], background: 'muted'),
            self::block(4, 'segment_tiles', [
                'heading' => 'Find the right fit',
                'tiles' => array_map(
                    fn (string $segment, string $image): array => [
                        'segment' => $segment,
                        'label' => DemoProjects::SEGMENT_LABELS[$segment],
                        'description' => self::SEGMENT_DESCRIPTIONS[$segment],
                        'image' => DemoMedia::image($image),
                        'url' => '/projects/type/'.str_replace('_', '-', $segment),
                    ],
                    array_keys(DemoProjects::SEGMENT_LABELS),
                    ['projects/projects-01', 'projects/projects-03', 'projects/projects-06', 'projects/projects-05'],
                ),
            ]),
            // Placeholder figures until the client's arrive.
            self::block(5, 'stats_counters', [
                'heading' => 'Built on a track record',
                'items' => [
                    ['value' => 20, 'suffix' => '+', 'label' => 'Years of experience', 'decimals' => 0],
                    ['value' => 48, 'suffix' => null, 'label' => 'Projects handed over', 'decimals' => 0],
                    ['value' => 3.2, 'suffix' => 'M sft', 'label' => 'Space delivered', 'decimals' => 1],
                    ['value' => 2500, 'suffix' => '+', 'label' => 'Families at home', 'decimals' => 0],
                ],
            ], background: 'dark', spacing: 'lg'),
            self::block(6, 'project_grid', [
                'heading' => 'Ongoing projects',
                'projects' => self::projectCards(lifecycle: 'ongoing', limit: 6),
                'view_all' => ['label' => 'View all ongoing projects', 'url' => '/projects/status/ongoing', 'new_tab' => false],
            ]),
            self::block(7, 'progress_highlight', [
                'heading' => 'Construction progress',
                'stages' => array_map(
                    fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
                    array_keys(self::PROGRESS_STAGES),
                    self::PROGRESS_STAGES,
                ),
                'items' => self::progressSummaries(),
            ], background: 'muted'),
            self::block(8, 'testimonials', [
                'heading' => 'In our buyers’ words',
                'layout' => 'carousel',
                'items' => self::testimonials(),
            ]),
            self::block(9, 'partners_marquee', [
                'heading' => 'Banks and partners we work with',
                'speed' => 'normal',
                'direction' => 'left',
                'partners' => self::partners(),
            ], spacing: 'sm'),
            self::block(10, 'latest_news', [
                'heading' => 'Latest news',
                'posts' => self::newsCards(limit: 3),
                'view_all' => ['label' => 'All news', 'url' => '/news', 'new_tab' => false],
            ], background: 'muted'),
            self::block(11, 'cta_band', [
                'heading' => 'Own land in Dhaka? Let’s build on it together.',
                'text' => 'Our joint-venture model gives landowners a fair share of the finished building, clear agreements and updates at every stage.',
                'primary_cta' => ['label' => 'Talk to our land team', 'url' => '/landowner', 'new_tab' => false],
                'secondary_cta' => ['label' => 'Call the hotline', 'url' => 'tel:'.self::SETTINGS['contact']['hotline'], 'new_tab' => false],
                'style' => 'gradient',
                'image' => null,
            ], background: 'dark', spacing: 'lg'),
        ];
    }

    /**
     * News cards (NewsCardResource, types/site.ts), newest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function newsCards(?string $category = null, ?int $limit = null): array
    {
        $posts = array_slice(array_values(array_filter(
            self::POSTS,
            fn (array $post): bool => $category === null || $post['category'] === $category,
        )), 0, $limit);

        return array_map(
            fn (array $post, int $index): array => [
                'id' => $index + 1,
                'slug' => $post['slug'],
                'url' => '/news/'.$post['slug'],
                'title' => $post['title'],
                'excerpt' => $post['excerpt'],
                'cover' => DemoMedia::image($post['cover']),
                'category' => [
                    'name' => self::POST_CATEGORIES[$post['category']],
                    'slug' => $post['category'],
                    'url' => '/news/category/'.$post['category'],
                ],
                'published_at' => $post['published_at'],
                'read_minutes' => $post['read_minutes'],
            ],
            $posts,
            array_keys($posts),
        );
    }

    /**
     * Testimonials (TestimonialResource) in sort order, optionally the `nrb` source only.
     *
     * @return list<array<string, mixed>>
     */
    public static function testimonials(bool $nrbOnly = false): array
    {
        $cards = [];

        foreach (self::TESTIMONIALS as $index => $row) {
            if ($nrbOnly && ! $row['is_nrb']) {
                continue;
            }

            $project = $row['project'] === null ? null : DemoProjects::row($row['project']);

            $cards[] = [
                'id' => $index + 1,
                'quote' => $row['quote'],
                'person_name' => $row['person_name'],
                'designation' => $row['designation'],
                'project' => $project === null ? null : ['name' => $project['name'], 'url' => '/projects/'.$project['slug']],
                'photo' => null,
                'is_nrb' => $row['is_nrb'],
            ];
        }

        return $cards;
    }

    /**
     * Partners (PartnerResource) in sort order.
     *
     * @return list<array<string, mixed>>
     */
    public static function partners(): array
    {
        return array_map(
            fn (array $partner, int $index): array => [
                'id' => $index + 1,
                'name' => $partner[0],
                'logo' => DemoMedia::image(sprintf('partners/partners-%02d', $index + 1)),
                'url' => null,
                'category' => $partner[1],
            ],
            self::PARTNERS,
            array_keys(self::PARTNERS),
        );
    }

    /**
     * CMP-PROG-01 summaries: the latest progress update of each project.
     *
     * @return list<array<string, mixed>>
     */
    private static function progressSummaries(): array
    {
        $summaries = [];

        foreach (self::PROGRESS as $update) {
            $project = DemoProjects::row($update['project']);

            $summaries[] = [
                'project' => [
                    'id' => $project['id'],
                    'name' => $project['name'],
                    'url' => '/projects/'.$project['slug'],
                    'location' => ['area' => DemoProjects::area($project['area'])['name'], 'city' => DemoProjects::area($project['area'])['city']],
                ],
                'percent' => $update['percent'],
                'stage' => $update['stage'],
                'stage_label' => self::PROGRESS_STAGES[$update['stage']],
                'reported_on' => $update['reported_on'],
                'note' => $update['note'],
                'photo' => DemoMedia::image($update['photo']),
            ];
        }

        return $summaries;
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
        // Facet URL slugs use hyphens (`handed-over`, owner in 4.1).
        $slug = ProjectFilters::urlSlug(...);

        $cities = [];

        foreach (DemoProjects::areas() as $area) {
            $cities[$area['city']][] = $option($area['slug'], $area['name'], '/locations/'.$area['slug']);
        }

        return [
            'action' => '/projects',
            'segments' => array_map(
                fn (string $value): array => $option($value, DemoProjects::SEGMENT_LABELS[$value], '/projects/type/'.$slug($value)),
                ['residential', 'commercial', 'land'],
            ),
            'statuses' => array_map(
                fn (string $value): array => $option($value, DemoProjects::LIFECYCLE_LABELS[$value], '/projects/status/'.$slug($value)),
                array_keys(DemoProjects::LIFECYCLE_LABELS),
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
     * Project cards (ProjectCard in types/site.ts), filtered like the
     * featured_projects / project_grid presets (spec/page-builder.md rows 4–5).
     *
     * @return list<array<string, mixed>>
     */
    public static function projectCards(bool $featuredOnly = false, ?string $segment = null, ?string $lifecycle = null, ?int $limit = null): array
    {
        $cards = [];

        foreach (DemoProjects::rows() as $row) {
            if (($featuredOnly && ! $row['featured'])
                || ($segment !== null && $row['segment'] !== $segment)
                || ($lifecycle !== null && $row['lifecycle'] !== $lifecycle)) {
                continue;
            }

            if ($limit !== null && count($cards) >= $limit) {
                break;
            }

            $cards[] = DemoProjects::card($row);
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
            $item('Residential', '/projects/type/residential', description: self::SEGMENT_DESCRIPTIONS['residential'], icon: 'house'),
            $item('Commercial', '/projects/type/commercial', description: self::SEGMENT_DESCRIPTIONS['commercial'], icon: 'building'),
            $item('Mixed-use', '/projects/type/mixed-use', description: self::SEGMENT_DESCRIPTIONS['mixed_use'], icon: 'layers'),
            $item('Land', '/projects/type/land', description: self::SEGMENT_DESCRIPTIONS['land'], icon: 'land-plot'),
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
            fn (array $area): array => $item($area['name'], '/locations/'.$area['slug'], count: $area['count']),
            DemoProjects::topAreas(),
        );

        // Contact is not a header item: the Enquire button links there (DEC-40).
        // Facet URL slugs use hyphens (`mixed-use`, `handed-over`; owner in 4.1).
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
            'overlay_opacity' => 55,
            'overlay_style' => 'solid',
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
     * DM-BLOCK envelope (CMS-PB-001). The hero is edge to edge (`none`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function block(int $id, string $type, array $data, string $background = 'default', ?string $spacing = null): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'is_visible' => true,
            'anchor_id' => null,
            'background' => $background,
            'spacing' => $spacing ?? ($type === 'hero' ? 'none' : 'md'),
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
