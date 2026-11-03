<?php

namespace App\Support\Demo;

use App\Support\Seo\SeoData;
use Illuminate\Support\Carbon;

/**
 * Part A news (FR-NEWS-001…007, DM-POST / DM-PCAT / DM-TAG): posts with
 * categories, tags, author, related projects and a body built from a small
 * block list, shaped like the later NewsCardResource / PostResource
 * (resources/js/types/news.ts). Placeholder copy for the client's review.
 * One post is dated in the future to prove AC-NEWS-01 (404 before its time).
 * Deleted with DemoContent in 16.4.
 *
 * @phpstan-type Block array<int, mixed>
 * @phpstan-type Post array{slug: string, title: string, excerpt: string, category: string, tags: list<string>, published_at: string, cover: string, featured: bool, author: string, projects: list<string>, body: list<Block>}
 * @phpstan-type NewsCardData array<string, mixed>
 */
final class DemoNews
{
    /** FR-NEWS / PG-12: nine posts per page. */
    public const int PER_PAGE = 9;

    /** FR-NEWS-004: reading speed. */
    private const int WORDS_PER_MINUTE = 200;

    /** SEO-ONP / PG-13: a tag page is indexable from this many published posts. */
    public const int TAG_INDEX_MIN = 3;

    /** DM-PCAT defaults (FR-NEWS-002): slug => [name, description]. */
    private const array CATEGORIES = [
        'news' => ['News', 'Company announcements, handovers and milestones from Gemcon City Limited.'],
        'press-releases' => ['Press releases', 'Official statements and launch announcements for journalists and partners.'],
        'buying-guides' => ['Buying guides', 'Plain-language guides to approvals, payments and choosing the right home or plot in Bangladesh.'],
        'construction-updates' => ['Construction updates', 'Progress from our building sites: structure, finishes and handover schedules.'],
        'events' => ['Events', 'Fairs, site-visit days and open houses where you can meet the Gemcon City team.'],
    ];

    /** DM-TAG: slug => name. */
    private const array TAGS = [
        'nrb' => 'NRB',
        'buyer-guide' => 'Buyer guide',
        'construction-progress' => 'Construction progress',
        'banani' => 'Banani',
        'gulshan' => 'Gulshan',
        'handover' => 'Handover',
        'bookings' => 'Bookings',
        'rajuk' => 'RAJUK',
        'land' => 'Land',
        'uttara' => 'Uttara',
    ];

    /** Authors: name => role (FR-NEWS-001: a team member or a user). */
    private const array AUTHORS = [
        'Farhana Rahman' => 'Managing Director',
        'Tanvir Hossain' => 'Director, Projects',
        'Imran Chowdhury' => 'Chief Engineer',
        'Sadia Islam' => 'Head of Sales',
        'Gemcon City editorial team' => 'Communications',
    ];

    /**
     * Newest first. Bodies start at H2 (FR-NEWS-005); the title is the page's H1.
     *
     * @return list<Post>
     */
    private static function posts(): array
    {
        $scheduled = Carbon::now('Asia/Dhaka')->addDays(30)->toDateString();

        return [
            [
                'slug' => 'gemcon-year-end-booking-offers', 'title' => 'Year-end booking offers on selected homes', 'excerpt' => 'A short window of fixed-rate booking offers on Gemcon Lakeview and Gemcon Maple Residency.',
                'category' => 'events', 'tags' => ['bookings'], 'published_at' => $scheduled, 'cover' => 'news/news-03', 'featured' => false, 'author' => 'Sadia Islam', 'projects' => ['gemcon-lakeview'],
                'body' => [['h2', 'What is on offer'], ['p', 'This post is scheduled and must not be visible before its publish date.']],
            ],
            [
                'slug' => 'gemcon-trade-centre-tops-out', 'title' => 'Gemcon Trade Centre tops out at 18 floors in Banani', 'excerpt' => 'The final roof slab was cast this month, keeping the commercial tower on track for handover in mid-2027.',
                'category' => 'construction-updates', 'tags' => ['banani', 'construction-progress'], 'published_at' => '2026-10-18', 'cover' => 'news/news-01', 'featured' => false, 'author' => 'Imran Chowdhury', 'projects' => ['gemcon-trade-centre'],
                'body' => [
                    ['h2', 'The last slab is poured'],
                    ['p', 'On the morning of 14 October our crew cast the roof slab of Gemcon Trade Centre, the eighteenth and final floor of the tower. The pour ran for eleven hours and was inspected by our structural engineer and the independent consultant at every stage.'],
                    ['p', 'With the frame complete, the work moves inside. Over the next six months the façade, lift installation and services run side by side, so that fit-out can start on the lower floors while the upper ones are still being closed in.'],
                    ['h2', 'What happens next'],
                    ['ul', ['Curtain wall installation, floors 1 to 18', 'Lift installation and testing', 'Mechanical, electrical and plumbing first fix', 'Lobby and podium finishes']],
                    ['p', 'We publish a photo update for every stage on the <a href="/projects/gemcon-trade-centre">project page</a>, so that buyers who cannot visit in person can still follow the work.'],
                    ['quote', 'A frame is only the beginning. The next six months decide how the building feels to work in.'],
                    ['p', 'Handover remains planned for mid-2027. Any change to that date will be announced here first.'],
                ],
            ],
            [
                'slug' => 'buying-an-apartment-from-abroad', 'title' => 'Buying an apartment in Dhaka from abroad: a step-by-step guide for NRBs', 'excerpt' => 'Power of attorney, remittance rules and site visits by video. Everything you need before booking from overseas.',
                'category' => 'buying-guides', 'tags' => ['nrb', 'buyer-guide'], 'published_at' => '2026-10-02', 'cover' => 'news/news-02', 'featured' => true, 'author' => 'Farhana Rahman', 'projects' => ['gemcon-lakeview', 'gemcon-maple-residency'],
                'body' => [
                    ['h2', 'Start with a clear brief'],
                    ['p', 'Most non-resident Bangladeshis who buy from abroad do so for one of three reasons: a home for family in Dhaka, a future retirement base, or an investment. Decide which it is before you speak to anyone. It changes the area you choose, the size you need and how much you pay attention to rental demand.'],
                    ['p', 'Write down your budget, your preferred areas and the date by which you want the keys. Our sales team will turn that into a short list of projects, with or without a visit.'],
                    ['h2', 'The documents you will need'],
                    ['ul', ['A valid passport and a recent photograph', 'Your National ID number, if you hold one', 'A tax identification number (TIN), if you have one', 'A power of attorney, if someone will sign for you in Dhaka']],
                    ['h3', 'Power of attorney'],
                    ['p', 'If you cannot be in Dhaka to sign, you can appoint a trusted person through a power of attorney. It is prepared and attested where you live, then verified for use in Bangladesh. Ask a lawyer to confirm the current requirements for your country before you pay anyone.'],
                    ['h2', 'How payments work'],
                    ['ol', ['Ask your bank which channels are currently permitted for sending money to Bangladesh.', 'Send every payment from an account in your own name.', 'Keep each remittance advice and share a copy with our sales team.', 'Collect a signed money receipt for each instalment.']],
                    ['quote', 'Every payment should leave a paper trail in your name.'],
                    ['table', ['Stage', 'What happens', 'Typical time'], [['Enquiry', 'A sales advisor shares options and prices', 'Same day'], ['Video site visit', 'A live walk-through of the site or sample flat', 'Within a week'], ['Booking', 'Allotment letter and first instalment', '2 to 3 days'], ['Registration', 'Deed signed and registered', 'Depends on payment plan']]],
                    ['figure', 'news/news-05', 'A sample flat walk-through, the same one we stream for overseas buyers.'],
                    ['h2', 'Visiting without flying'],
                    ['p', 'You do not need to travel to see a project. We run scheduled video walk-throughs on site, and can send a short film of the exact floor and orientation you are considering. If you do plan a trip, book a site visit through the project page and we will arrange transport from the airport.'],
                    ['p', 'For a longer introduction to how we work with overseas buyers, see our <a href="/nrb">NRB page</a>.'],
                ],
            ],
            [
                'slug' => 'rehab-fair-2026', 'title' => 'Meet us at the REHAB Fair 2026', 'excerpt' => 'Visit our pavilion for fair-only booking offers on Gemcon Lakeview and Gemcon Maple Residency.',
                'category' => 'events', 'tags' => ['bookings'], 'published_at' => '2026-09-21', 'cover' => 'news/news-03', 'featured' => false, 'author' => 'Sadia Islam', 'projects' => ['gemcon-lakeview', 'gemcon-maple-residency'],
                'body' => [
                    ['h2', 'Find our pavilion'],
                    ['p', 'We will be at the REHAB Fair for all four days with a full-size sample apartment, scale models of every active project and a team that can answer questions on prices, payment plans and approvals.'],
                    ['h2', 'Fair-only offers'],
                    ['p', 'Bookings made at the pavilion during the fair come with a fixed price for the first instalment and a free parking slot on selected floors of Gemcon Lakeview and Gemcon Maple Residency.'],
                    ['p', 'Bring your National ID or passport. If you would rather talk first, <a href="/contact">contact us</a> and we will reserve a time slot for you.'],
                ],
            ],
            [
                'slug' => 'gemcon-orchid-heights-launch', 'title' => 'Gemcon Orchid Heights launches in Gulshan', 'excerpt' => 'Twenty-four four-bedroom residences on a quiet lakeside plot, with bookings now open.',
                'category' => 'press-releases', 'tags' => ['gulshan'], 'published_at' => '2026-09-05', 'cover' => 'news/news-04', 'featured' => false, 'author' => 'Gemcon City editorial team', 'projects' => ['gemcon-orchid-heights'],
                'body' => [
                    ['h2', 'A small building on a big plot'],
                    ['p', 'Gemcon Orchid Heights has twenty-four residences on one lakeside site. Every home has four bedrooms, two aspects and a private lift lobby, and no floor has more than two homes.'],
                    ['p', 'Prices are available on request. Bookings are open for the first twelve homes, with handover planned for the end of 2029.'],
                    ['h2', 'About Gemcon City Limited'],
                    ['p', 'Gemcon City Limited is a Bangladeshi real-estate developer building homes, offices and mixed-use projects in Dhaka and Chattogram. Media enquiries: <a href="/contact">contact our communications team</a>.'],
                ],
            ],
            [
                'slug' => 'understanding-rajuk-approval', 'title' => 'What a RAJUK approval tells you about a project', 'excerpt' => 'How to read an approval letter and which documents to ask for before you sign.',
                'category' => 'buying-guides', 'tags' => ['rajuk', 'buyer-guide'], 'published_at' => '2026-08-17', 'cover' => 'news/news-05', 'featured' => false, 'author' => 'Tanvir Hossain', 'projects' => [],
                'body' => [
                    ['h2', 'Why approvals matter'],
                    ['p', 'An approval from the planning authority tells you that the building has been checked against the plot size, the permitted height and the road width. It does not tell you that the builder will finish on time, so treat it as the first question rather than the last.'],
                    ['h2', 'What to ask for'],
                    ['ul', ['The approval letter and its reference number', 'The approved drawings for your floor', 'The land title documents and mutation records', 'The builder’s registered company details']],
                    ['p', 'Ask to see the originals and compare the reference number on every page. A reputable developer will not mind.'],
                    ['h2', 'Check the number'],
                    ['p', 'Every Gemcon City project page shows its approval number in the at-a-glance facts, and you can copy it with one tap to check against your own lawyer’s search.'],
                    ['p', 'Rules and fees change, so confirm current requirements with the authority or your lawyer before you pay.'],
                ],
            ],
            [
                'slug' => 'handover-gemcon-palm-court', 'title' => 'Keys handed over at Gemcon Palm Court', 'excerpt' => 'Families moved into all 32 apartments of our Baridhara project this summer.',
                'category' => 'news', 'tags' => ['handover'], 'published_at' => '2026-07-29', 'cover' => 'news/news-06', 'featured' => false, 'author' => 'Gemcon City editorial team', 'projects' => ['gemcon-palm-court'],
                'body' => [
                    ['h2', 'Thirty-two homes, one weekend'],
                    ['p', 'Over a single weekend in July we handed over every apartment at Gemcon Palm Court. Each family received a handover folder with the as-built drawings, warranty terms and a list of contacts for after-sales.'],
                    ['p', 'The after-sales team stays on site for the first month to deal with any snag the same day.'],
                    ['quote', 'We moved in on Saturday and the lights, the water and the lift were all working. That is what I wanted.'],
                ],
            ],
            [
                'slug' => 'site-inspection-stages-explained', 'title' => 'The six site inspection stages we publish for every project', 'excerpt' => 'From foundation to finishing: what is checked at each stage and who signs it off.',
                'category' => 'construction-updates', 'tags' => ['construction-progress', 'buyer-guide'], 'published_at' => '2026-07-10', 'cover' => 'news/news-02', 'featured' => false, 'author' => 'Imran Chowdhury', 'projects' => [],
                'body' => [
                    ['h2', 'Why we publish stages'],
                    ['p', 'Buyers are asked to pay by stage, so they should be able to see the stage. For every project we publish a dated photo set after each of six checks: foundation, structure, brickwork, services, finishing and handover.'],
                    ['p', 'Each set is signed off by our site engineer and, for structure and services, by an independent consultant.'],
                ],
            ],
            [
                'slug' => 'nrb-remittance-and-booking-checklist', 'title' => 'A booking checklist for overseas buyers', 'excerpt' => 'Ten things to confirm before you send the first instalment from abroad.',
                'category' => 'buying-guides', 'tags' => ['nrb', 'bookings'], 'published_at' => '2026-06-24', 'cover' => 'news/news-04', 'featured' => false, 'author' => 'Sadia Islam', 'projects' => [],
                'body' => [
                    ['h2', 'Before you send money'],
                    ['ol', ['Confirm the project name, tower and floor in writing.', 'Ask for the payment schedule with dates.', 'Check that the receipt carries the company’s registered details.', 'Keep your bank advice with the receipt.']],
                    ['p', 'This checklist is a starting point. Always confirm current rules with your bank and a lawyer.'],
                ],
            ],
            [
                'slug' => 'orchid-heights-site-visit-days', 'title' => 'Orchid Heights site-visit days this month', 'excerpt' => 'Walk the lakeside plot with the design team on three Saturdays.',
                'category' => 'events', 'tags' => ['gulshan', 'bookings'], 'published_at' => '2026-06-08', 'cover' => 'news/news-03', 'featured' => false, 'author' => 'Sadia Islam', 'projects' => ['gemcon-orchid-heights'],
                'body' => [
                    ['h2', 'Three Saturdays, small groups'],
                    ['p', 'Visits run in groups of eight so that everyone can ask questions. Book a slot through the project page and we will send directions and a parking pass.'],
                ],
            ],
            [
                'slug' => 'gemcon-greenfield-ready-to-move', 'title' => 'Gemcon Greenfield is ready to move in', 'excerpt' => 'The last units beside the Sector 7 park are now available for immediate handover.',
                'category' => 'news', 'tags' => ['handover', 'uttara'], 'published_at' => '2026-05-27', 'cover' => 'news/news-06', 'featured' => false, 'author' => 'Gemcon City editorial team', 'projects' => ['gemcon-greenfield'],
                'body' => [
                    ['h2', 'Ready means ready'],
                    ['p', 'The remaining apartments at Gemcon Greenfield have their final inspection certificates and can be handed over within days of the final instalment.'],
                ],
            ],
            [
                'slug' => 'apartment-or-plot', 'title' => 'Choosing between an apartment and a plot', 'excerpt' => 'Two ways to own property in Dhaka, and the questions that decide which suits you.',
                'category' => 'buying-guides', 'tags' => ['buyer-guide', 'land'], 'published_at' => '2026-05-12', 'cover' => 'news/news-01', 'featured' => false, 'author' => 'Farhana Rahman', 'projects' => ['gemcon-riverside-plots'],
                'body' => [
                    ['h2', 'Think about time'],
                    ['p', 'A plot gives you freedom but needs time, money and attention to build. An apartment gives you a finished home, shared services and a handover date.'],
                    ['h2', 'Think about cost'],
                    ['p', 'A plot looks cheaper per square foot until you add design, approvals, construction and supervision. Ask for a full estimate before you compare.'],
                ],
            ],
            [
                'slug' => 'lakeview-structure-reaches-seventh-floor', 'title' => 'Gemcon Lakeview structure reaches the seventh floor', 'excerpt' => 'Half of the structure is complete, with the rooftop garden slab next.',
                'category' => 'construction-updates', 'tags' => ['construction-progress'], 'published_at' => '2026-04-30', 'cover' => 'news/news-05', 'featured' => false, 'author' => 'Imran Chowdhury', 'projects' => ['gemcon-lakeview'],
                'body' => [
                    ['h2', 'Halfway up'],
                    ['p', 'The frame of Gemcon Lakeview now stands at seven floors. Brickwork has started on the lower levels and the first lift shaft has been signed off.'],
                ],
            ],
            [
                'slug' => 'new-sales-centre-banani', 'title' => 'A new sales centre opens in Banani', 'excerpt' => 'Sample flats, scale models and a quiet room to talk through your plans.',
                'category' => 'press-releases', 'tags' => ['banani'], 'published_at' => '2026-04-14', 'cover' => 'news/news-04', 'featured' => false, 'author' => 'Gemcon City editorial team', 'projects' => [],
                'body' => [
                    ['h2', 'Open seven days'],
                    ['p', 'The Banani sales centre opens on 14 April with a full-size sample apartment and models of every active project. No appointment is needed.'],
                ],
            ],
        ];
    }

    /**
     * Published posts (publish date reached), newest first, optionally
     * without one slug (the featured post on PG-12).
     *
     * @return list<Post>
     */
    public static function published(?string $without = null): array
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();

        return array_values(array_filter(
            self::posts(),
            fn (array $post): bool => $post['published_at'] <= $today && $post['slug'] !== $without,
        ));
    }

    /**
     * AC-NEWS-01: null for an unknown slug and for a post scheduled in the future.
     *
     * @return Post|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::published() as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * The newest published post with the featured flag.
     *
     * @return Post|null
     */
    public static function featured(): ?array
    {
        foreach (self::published() as $post) {
            if ($post['featured']) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @param  list<Post>  $posts
     * @return list<Post>
     */
    public static function inCategory(array $posts, string $category): array
    {
        return array_values(array_filter($posts, fn (array $post): bool => $post['category'] === $category));
    }

    /**
     * @param  list<Post>  $posts
     * @return list<Post>
     */
    public static function withTag(array $posts, string $tag): array
    {
        return array_values(array_filter($posts, fn (array $post): bool => in_array($tag, $post['tags'], true)));
    }

    /**
     * @return array{slug: string, name: string, description: string, url: string, count: int}|null
     */
    public static function category(string $slug): ?array
    {
        if (! isset(self::CATEGORIES[$slug])) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => self::CATEGORIES[$slug][0],
            'description' => self::CATEGORIES[$slug][1],
            'url' => '/news/category/'.$slug,
            'count' => count(self::inCategory(self::published(), $slug)),
        ];
    }

    /**
     * FR-NEWS-002 order.
     *
     * @return list<array{slug: string, name: string, description: string, url: string, count: int}>
     */
    public static function categories(): array
    {
        return array_map(
            fn (string $slug): array => self::category($slug) ?? throw new \LogicException("Unknown category {$slug}."),
            array_keys(self::CATEGORIES),
        );
    }

    /**
     * @return array{slug: string, name: string, url: string, count: int}|null
     */
    public static function tag(string $slug): ?array
    {
        if (! isset(self::TAGS[$slug])) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => self::TAGS[$slug],
            'url' => '/news/tag/'.$slug,
            'count' => count(self::withTag(self::published(), $slug)),
        ];
    }

    /**
     * NewsCardResource (types/news.ts); `id` follows the post's place in the list.
     *
     * @param  Post  $post
     * @return NewsCardData
     */
    public static function card(array $post): array
    {
        $category = self::category($post['category']);
        $ids = array_flip(array_column(self::posts(), 'slug'));

        return [
            'id' => $ids[$post['slug']] + 1,
            'slug' => $post['slug'],
            'url' => '/news/'.$post['slug'],
            'title' => $post['title'],
            'excerpt' => $post['excerpt'],
            'cover' => DemoMedia::image($post['cover']),
            'category' => ['name' => $category['name'] ?? '', 'slug' => $post['category'], 'url' => '/news/category/'.$post['category']],
            'published_at' => $post['published_at'],
            'read_minutes' => self::readMinutes($post),
        ];
    }

    /**
     * Home block and listing cards, newest published first.
     *
     * @return list<NewsCardData>
     */
    public static function cards(?string $category = null, ?int $limit = null): array
    {
        $posts = $category === null ? self::published() : self::inCategory(self::published(), $category);

        return array_map(self::card(...), array_slice($posts, 0, $limit));
    }

    /**
     * FR-NEWS-007: the three newest published posts of the same category,
     * excluding itself; the newest overall fill any gap.
     *
     * @param  Post  $post
     * @return list<NewsCardData>
     */
    public static function related(array $post, int $count = 3): array
    {
        $others = self::published($post['slug']);
        $same = array_slice(self::inCategory($others, $post['category']), 0, $count);
        $slugs = array_column($same, 'slug');

        $fill = array_filter($others, fn (array $other): bool => ! in_array($other['slug'], $slugs, true));
        $chosen = [...$same, ...array_slice(array_values($fill), 0, $count - count($same))];

        return array_map(self::card(...), $chosen);
    }

    /**
     * The PostResource body of PG-14.
     *
     * @param  Post  $post
     * @return array<string, mixed>
     */
    public static function article(array $post): array
    {
        $tags = array_map(
            fn (string $slug): array => ['name' => self::TAGS[$slug], 'slug' => $slug, 'url' => '/news/tag/'.$slug],
            $post['tags'],
        );

        $projects = array_map(
            fn (string $slug): array => DemoProjects::card(DemoProjects::row($slug)),
            $post['projects'],
        );

        return [
            ...self::card($post),
            'body' => self::bodyHtml($post['body']),
            'author' => ['name' => $post['author'], 'role' => self::AUTHORS[$post['author']] ?? ''],
            'tags' => $tags,
            'projects' => $projects,
        ];
    }

    /**
     * FR-NEWS-004: ceil(words / 200), at least one minute.
     *
     * @param  Post  $post
     */
    public static function readMinutes(array $post): int
    {
        $text = trim(strip_tags(self::bodyHtml($post['body'])));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Absolute 1200×630 OG image for a post (SEO-ONP-006).
     *
     * @param  Post  $post
     */
    public static function ogImage(array $post): ?string
    {
        return DemoMedia::ogImage($post['cover']);
    }

    /**
     * CMP-SHARE-01: share-intent URLs on the three networks (no SDKs).
     *
     * @param  Post  $post
     * @return array<string, mixed>
     */
    public static function share(array $post): array
    {
        $copy = self::copy();
        $url = SeoData::absoluteUrl('/news/'.$post['slug']);
        $encodedUrl = rawurlencode($url);

        return [
            'url' => $url,
            'title' => $post['title'],
            'heading' => $copy['share_heading'],
            'copy_label' => $copy['share_copy_label'],
            'copied_message' => $copy['share_copied'],
            'failed_message' => $copy['share_failed'],
            'links' => [
                ['network' => 'facebook', 'label' => 'Facebook', 'href' => 'https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl],
                ['network' => 'linkedin', 'label' => 'LinkedIn', 'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$encodedUrl],
                ['network' => 'whatsapp', 'label' => 'WhatsApp', 'href' => 'https://wa.me/?text='.rawurlencode($post['title'].' '.$url)],
            ],
        ];
    }

    /**
     * Labels and headings of PG-12…14 (CMS-000a: copy comes from props).
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'index_title' => 'News and insights',
            'index_intro' => 'Construction updates, buying guides and announcements from Gemcon City Limited.',
            'index_description' => 'Construction progress, buying guides, events and announcements from Gemcon City Limited, a Bangladeshi real-estate developer.',
            'all_label' => 'All',
            'categories_label' => 'Categories',
            'tag_title' => 'Tagged “{tag}”',
            'tag_intro' => 'Articles tagged {tag}.',
            'tag_description' => 'Gemcon City articles tagged {tag}.',
            'empty_title' => 'No articles here yet',
            'empty_body' => 'Nothing has been published in this section. Read all of our news instead.',
            'empty_action' => 'All news',
            'by_label' => 'By',
            'projects_heading' => 'Projects in this article',
            'tags_label' => 'Tagged',
            'related_heading' => 'Keep reading',
            'related_view_all' => 'All news',
            'newsletter_heading' => 'Construction updates and buying guides, once a month',
            'newsletter_text' => 'New launches, progress photos and plain-language guides. No spam, unsubscribe any time.',
            'share_heading' => 'Share this article',
            'share_copy_label' => 'Copy link',
            'share_copied' => 'Link copied',
            'share_failed' => 'Could not copy the link. Copy it from the address bar instead.',
        ];
    }

    /**
     * Body blocks as the sanitised HTML the rich-text editor will store
     * (FR-NEWS-001: H2–H4, lists, quote, table, image with caption).
     *
     * @param  list<Block>  $blocks
     */
    private static function bodyHtml(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= match ($block[0]) {
                'h2', 'h3', 'h4', 'p' => "<{$block[0]}>{$block[1]}</{$block[0]}>",
                'ul', 'ol' => "<{$block[0]}>".implode('', array_map(fn (string $item): string => "<li>{$item}</li>", $block[1]))."</{$block[0]}>",
                'quote' => "<blockquote><p>{$block[1]}</p></blockquote>",
                'table' => self::tableHtml($block[1], $block[2]),
                'figure' => self::figureHtml($block[1], $block[2]),
                default => '',
            };
        }

        return $html;
    }

    /**
     * @param  list<string>  $head
     * @param  list<list<string>>  $rows
     */
    private static function tableHtml(array $head, array $rows): string
    {
        $cells = fn (string $tag, array $row): string => '<tr>'.implode('', array_map(fn (string $cell): string => "<{$tag}>{$cell}</{$tag}>", $row)).'</tr>';

        return '<table><thead>'.$cells('th', $head).'</thead><tbody>'
            .implode('', array_map(fn (array $row): string => $cells('td', $row), $rows))
            .'</tbody></table>';
    }

    /**
     * FR-NEWS-006: an image from the media library with its caption.
     */
    private static function figureHtml(string $key, string $caption): string
    {
        $image = DemoMedia::image($key);

        if ($image === null) {
            return '';
        }

        $sizes = '(min-width:1024px) 700px, 92vw';
        $sources = '';

        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $type) {
            if ($image['sources'][$format] !== null && $image['sources'][$format] !== '') {
                $sources .= '<source type="'.$type.'" srcset="'.e($image['sources'][$format]).'" sizes="'.$sizes.'">';
            }
        }

        $img = '<img src="'.e($image['fallback']).'" alt="'.e($image['alt']).'" width="'.$image['width'].'" height="'.$image['height'].'" loading="lazy" decoding="async">';

        return '<figure><picture>'.$sources.$img.'</picture><figcaption>'.e($caption).'</figcaption></figure>';
    }
}
