<?php

namespace Database\Seeders\Data;

use Illuminate\Support\Carbon;

/**
 * Seed source of the demo news (13.x style, DEC-83): the placeholder posts, categories and tags the
 * `NewsSeeder` writes into the real tables outside production. The public site and the admin read the
 * tables only. One post is dated in the future to prove AC-NEWS-01 (404 before its time). Deleted
 * with the other demo sources in 16.4.
 *
 * @phpstan-type Block array<int, mixed>
 * @phpstan-type Post array{slug: string, title: string, excerpt: string, category: string, tags: list<string>, published_at: string, cover: string, featured: bool, author: string, projects: list<string>, body: list<Block>}
 */
final class DemoNews
{
    /** DM-PCAT defaults (FR-NEWS-002): slug => [name, description]. */
    public const array CATEGORIES = [
        'news' => ['News', 'Company announcements, handovers and milestones from Gemcon City Limited.'],
        'press-releases' => ['Press releases', 'Official statements and launch announcements for journalists and partners.'],
        'buying-guides' => ['Buying guides', 'Plain-language guides to approvals, payments and choosing the right home or plot in Bangladesh.'],
        'construction-updates' => ['Construction updates', 'Progress from our building sites: structure, finishes and handover schedules.'],
        'events' => ['Events', 'Fairs, site-visit days and open houses where you can meet the Gemcon City team.'],
    ];

    /** DM-TAG: slug => name. */
    public const array TAGS = [
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

    /**
     * Every demo post, scheduled one included.
     *
     * @return list<Post>
     */
    public static function all(): array
    {
        return self::posts();
    }

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
     * FR-NEWS-002: the default categories, in order.
     *
     * @return list<array{slug: string, name: string, description: string}>
     */
    public static function categories(): array
    {
        return array_map(
            fn (string $slug, array $row): array => ['slug' => $slug, 'name' => $row[0], 'description' => $row[1]],
            array_keys(self::CATEGORIES),
            array_values(self::CATEGORIES),
        );
    }

    /** The scheduled post's publish time (Asia/Dhaka, 09:00) or a past post's date at 09:00. */
    public static function publishedAt(string $date): Carbon
    {
        return Carbon::parse($date.' 09:00', 'Asia/Dhaka');
    }

    /**
     * The body blocks as the HTML the rich-text editor stores (FR-NEWS-001: H2–H4, lists, quote, table,
     * library image with caption). `$image` turns a demo photo key into `[asset id, alt, src, width, height]`.
     *
     * @param  list<Block>  $blocks
     * @param  callable(string): (array{0: int, 1: string, 2: string, 3: int, 4: int}|null)  $image
     */
    public static function bodyHtml(array $blocks, callable $image): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= match ($block[0]) {
                'h2', 'h3', 'h4', 'p' => "<{$block[0]}>{$block[1]}</{$block[0]}>",
                'ul', 'ol' => "<{$block[0]}>".implode('', array_map(fn (string $item): string => "<li>{$item}</li>", $block[1]))."</{$block[0]}>",
                'quote' => "<blockquote><p>{$block[1]}</p></blockquote>",
                'table' => self::tableHtml($block[1], $block[2]),
                'figure' => self::figureHtml($image($block[1]), $block[2]),
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
     * FR-NEWS-006: an image from the media library with its caption; the alt text is the asset's, or
     * the caption when the asset has none.
     *
     * @param  array{0: int, 1: string, 2: string, 3: int, 4: int}|null  $image
     */
    private static function figureHtml(?array $image, string $caption): string
    {
        if ($image === null) {
            return '';
        }

        [$id, $alt, $src, $width, $height] = $image;

        return '<figure><img src="'.e($src).'" alt="'.e($alt !== '' ? $alt : $caption).'" width="'.$width.'" height="'.$height.'" data-asset-id="'.$id.'"><figcaption>'.e($caption).'</figcaption></figure>';
    }
}
