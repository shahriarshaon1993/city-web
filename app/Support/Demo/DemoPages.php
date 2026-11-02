<?php

namespace App\Support\Demo;

use App\Support\Seo\Breadcrumbs;

/**
 * Part A content pages of 6.3: the seeded block orders of LAY-ABOUT /
 * SERVICES / LANDOWNER / NRB / CUSTOM (design/layouts.md), the leadership
 * page (LAY-TEAM), the legal pages (LAY-LEGAL) and two custom pages (PG-25).
 * Blocks use the DM-BLOCK shapes of resources/js/types/blocks.ts; the copy is
 * placeholder text for the client's review. Deleted with DemoContent in 16.4
 * (pages then come from the `pages` table, 16.1).
 *
 * @phpstan-type BuilderPage array{title: string, description: string, blocks: list<array<string, mixed>>, index: bool}
 * @phpstan-type LegalPage array{title: string, description: string, updated_at: string, body: string}
 */
final class DemoPages
{
    /** @var list<string> */
    public const array BUILDER = ['about', 'leadership', 'services', 'landowner', 'nrb'];

    /** @var list<string> */
    public const array LEGAL = ['privacy-policy', 'terms'];

    /**
     * A builder page by key (`about`, `leadership`, `services`, `landowner`,
     * `nrb`) or a custom page by slug; null when the slug is unknown.
     *
     * @return BuilderPage|null
     */
    public static function builder(string $key): ?array
    {
        return match ($key) {
            'about' => self::about(),
            'leadership' => self::leadership(),
            'services' => self::services(),
            'landowner' => self::landowner(),
            'nrb' => self::nrb(),
            'sustainability' => self::sustainability(),
            'buyer-guide' => self::buyerGuide(),
            default => null,
        };
    }

    /**
     * @return LegalPage|null
     */
    public static function legal(string $key): ?array
    {
        return match ($key) {
            'privacy-policy' => [
                'title' => 'Privacy policy',
                'description' => 'How Gemcon City Limited collects, uses and protects the personal information you share through this website.',
                'updated_at' => '2026-11-01',
                'body' => self::privacyBody(),
            ],
            'terms' => [
                'title' => 'Terms of use',
                'description' => 'The terms that apply when you use the Gemcon City Limited website and its enquiry forms.',
                'updated_at' => '2026-11-01',
                'body' => self::termsBody(),
            ],
            default => null,
        };
    }

    /**
     * PG-07, LAY-ABOUT: page_header (image) → intro_statement → tabbed_pillars
     * → timeline → stats_counters → image_text (parent group) → awards →
     * partners_marquee → cta_band.
     *
     * @return BuilderPage
     */
    private static function about(): array
    {
        return [
            'title' => 'About us',
            'description' => 'Gemcon City Limited is a Bangladeshi real estate developer, part of Gemcon Group, building apartments, commercial spaces and land projects with clear titles.',
            'index' => true,
            'blocks' => [
                DemoBlocks::block(1, 'page_header', [
                    'title' => 'Homes and workplaces built to hold their value',
                    'intro' => 'Gemcon City Limited has developed apartments, offices and plots in Dhaka and beyond for two decades, with clear titles, approved designs and handovers on the dates we promise.',
                    'image' => DemoMedia::image('hero/hero-02'),
                    'breadcrumbs' => Breadcrumbs::to('About us'),
                ], spacing: 'none'),
                DemoBlocks::block(2, 'intro_statement', [
                    'eyebrow' => 'Our story',
                    'heading' => 'We started with one building and a promise to finish it properly.',
                    'body' => '<p>Gemcon City began in 2004 with twelve apartments in Dhanmondi. The buyers were neighbours and friends, so every delay and every shortcut would have been noticed. That discipline became the way we work.</p><p>Today we build for thousands of families and businesses, and we still publish approvals, progress and handover dates so that anyone can check our word.</p>',
                    'cta' => self::link('Meet our leadership', '/about/leadership'),
                    'image' => DemoMedia::image('projects/projects-04'),
                ], spacing: 'lg'),
                DemoBlocks::block(3, 'tabbed_pillars', [
                    'heading' => 'Mission, vision and values',
                    'tabs' => [
                        ['label' => 'Mission', 'title' => 'Build what we promise, and show our work', 'text' => 'We deliver homes and workplaces that match their brochures: the area, the finishes and the date. Approvals, progress photos and inspection stages are published so buyers never have to chase us for the facts.', 'image' => DemoMedia::image('interiors/interiors-01')],
                        ['label' => 'Vision', 'title' => 'The most trusted name in Bangladeshi property', 'text' => 'We want a Gemcon City address to be the safest way to buy property in the country, for families in Dhaka and for Bangladeshis living abroad.', 'image' => DemoMedia::image('locations/locations-03')],
                        ['label' => 'Values', 'title' => 'Integrity, craft and care', 'text' => 'We put prices and terms in writing, specify materials for decades of use, and stay with each building long after handover. These three habits decide every choice we make.', 'image' => DemoMedia::image('interiors/interiors-03')],
                    ],
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(4, 'timeline', [
                    'heading' => 'Two decades of building',
                    'items' => DemoBlocks::timelineItems(),
                ], spacing: 'lg'),
                DemoBlocks::block(5, 'stats_counters', [
                    'heading' => 'Built on a track record',
                    'items' => DemoContent::statItems(),
                ], background: 'dark', spacing: 'lg'),
                DemoBlocks::block(6, 'image_text', [
                    'heading' => 'Part of Gemcon Group',
                    'body' => '<p>Gemcon City Limited is the real estate arm of Gemcon Group, a Bangladeshi business group with interests in construction materials, engineering and trading.</p><p>The Group’s supply chain and engineering teams give our projects steady access to tested materials and experienced site managers.</p>',
                    'image' => DemoMedia::image('progress/progress-01'),
                    'image_side' => 'right',
                    'cta' => null,
                    'reveal' => 'mask',
                ], spacing: 'lg'),
                DemoBlocks::block(7, 'awards', [
                    'heading' => 'Recognition and certifications',
                    'items' => DemoBlocks::awardItems(),
                ], background: 'muted'),
                DemoBlocks::block(8, 'partners_marquee', [
                    'heading' => 'Banks and partners we work with',
                    'speed' => 'normal',
                    'direction' => 'left',
                    'partners' => DemoContent::partners(),
                ], spacing: 'sm'),
                DemoBlocks::block(9, 'cta_band', [
                    'heading' => 'See the work for yourself.',
                    'text' => 'Visit a project with our sales team, or call the hotline and we will arrange a time that suits you.',
                    'primary_cta' => self::link('Explore projects', '/projects'),
                    'secondary_cta' => self::link('Contact us', '/contact'),
                    'style' => 'gradient',
                    'image' => null,
                ], background: 'dark', spacing: 'lg'),
            ],
        ];
    }

    /**
     * PG-08, LAY-TEAM: page header, then the leadership, management and team
     * groups from the team members table.
     *
     * @return BuilderPage
     */
    private static function leadership(): array
    {
        return [
            'title' => 'Leadership',
            'description' => 'Meet the chairman, directors, managers and team behind Gemcon City Limited.',
            'index' => true,
            'blocks' => [
                DemoBlocks::block(1, 'page_header', [
                    'title' => 'The people behind Gemcon City',
                    'intro' => 'A board that signs every handover certificate, and a team that answers the phone after it.',
                    'image' => null,
                    'breadcrumbs' => Breadcrumbs::to('Leadership', [['About us', '/about']]),
                ]),
                DemoBlocks::block(2, 'team_grid', [
                    'heading' => 'Leadership',
                    'group' => 'leadership',
                    'members' => DemoTeam::members('leadership'),
                ], spacing: 'lg'),
                DemoBlocks::block(3, 'team_grid', [
                    'heading' => 'Management',
                    'group' => 'management',
                    'members' => DemoTeam::members('management'),
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(4, 'team_grid', [
                    'heading' => 'Our team',
                    'group' => 'team',
                    'members' => DemoTeam::members('team'),
                ], spacing: 'lg'),
            ],
        ];
    }

    /**
     * PG-09, LAY-SERVICES: page_header → feature_cards (5 services) →
     * process_steps → image_text ×2 → faq → cta_band.
     *
     * @return BuilderPage
     */
    private static function services(): array
    {
        return [
            'title' => 'Services',
            'description' => 'Apartments, commercial spaces, plots, joint ventures and after-sales care from Gemcon City Limited, from first visit to handover and beyond.',
            'index' => true,
            'blocks' => [
                DemoBlocks::block(1, 'page_header', [
                    'title' => 'Everything between a plot of land and your front door',
                    'intro' => 'We develop, sell and look after homes, workplaces and land, and we partner with landowners who want a building on their plot.',
                    'image' => null,
                    'breadcrumbs' => Breadcrumbs::to('Services'),
                ]),
                DemoBlocks::block(2, 'feature_cards', [
                    'heading' => 'What we offer',
                    'columns' => 3,
                    'tilt' => false,
                    'items' => [
                        ['icon' => 'building-2', 'image' => null, 'title' => 'Residential', 'text' => 'Apartments for families, from compact two-bedroom homes to four-bedroom residences, designed around daylight and cross-ventilation.', 'link' => self::link('Residential projects', '/projects/type/residential')],
                        ['icon' => 'landmark', 'image' => null, 'title' => 'Commercial', 'text' => 'Offices and retail floors in business districts, with flexible layouts, backup power and professional building management.', 'link' => self::link('Commercial projects', '/projects/type/commercial')],
                        ['icon' => 'map-pin', 'image' => null, 'title' => 'Land and plots', 'text' => 'Plots with clear titles in planned blocks, with the deed history and mutation records shared before you book.', 'link' => self::link('Land projects', '/projects/type/land')],
                        ['icon' => 'handshake', 'image' => null, 'title' => 'Joint ventures', 'text' => 'We build on your land under a registered agreement that fixes your share of the finished building in writing.', 'link' => self::link('For landowners', '/landowner')],
                        ['icon' => 'users', 'image' => null, 'title' => 'After-sales care', 'text' => 'A dedicated team handles snagging, utility transfers and warranty claims through the first year of ownership.', 'link' => self::link('Talk to us', '/contact')],
                    ],
                ], spacing: 'lg'),
                DemoBlocks::block(3, 'process_steps', [
                    'heading' => 'How a project runs with us',
                    'steps' => [
                        ['title' => 'Land and approvals', 'text' => 'We verify the title, survey the site and obtain RAJUK approval before a project is announced.'],
                        ['title' => 'Design and launch', 'text' => 'Architects and engineers finalise plans, and the approved design, prices and schedule are published.'],
                        ['title' => 'Booking and agreement', 'text' => 'You reserve a unit, review the allotment agreement with our legal team and sign it.'],
                        ['title' => 'Construction and updates', 'text' => 'You receive monthly progress photos and engineer’s notes while the building rises.'],
                        ['title' => 'Handover and care', 'text' => 'We complete snagging, transfer utilities and stay on call for the first year.'],
                    ],
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(4, 'image_text', [
                    'heading' => 'Planned around the people who live in them',
                    'body' => '<p>Every layout is tested for light, air and storage before it is approved. We would rather lose a saleable square foot than build a room that cannot be ventilated.</p>',
                    'image' => DemoMedia::image('interiors/interiors-02'),
                    'image_side' => 'right',
                    'cta' => self::link('See our projects', '/projects'),
                    'reveal' => 'mask',
                ], spacing: 'lg'),
                DemoBlocks::block(5, 'image_text', [
                    'heading' => 'Looked after once the keys change hands',
                    'body' => '<p>Handover is the start of a longer relationship. Our after-sales team walks you through the unit, transfers the utilities and advises your owners’ association on maintenance and sinking funds.</p>',
                    'image' => DemoMedia::image('interiors/interiors-04'),
                    'image_side' => 'left',
                    'cta' => null,
                    'reveal' => 'none',
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(6, 'faq', [
                    'heading' => 'Questions about our services',
                    'items' => [
                        ['id' => 1, 'question' => 'Do you only sell new apartments?', 'answer' => '<p>No. We also sell commercial floors and plots, and a few ready apartments from completed projects are usually available. Browse <a href="/projects/status/ready">ready projects</a> to see what is open today.</p>'],
                        ['id' => 2, 'question' => 'Can you help me arrange a home loan?', 'answer' => '<p>Our sales advisors work with several partner banks and can explain the documents each one asks for. The loan itself is agreed between you and the bank.</p>'],
                        ['id' => 3, 'question' => 'What does after-sales care include?', 'answer' => '<p>A snagging walk-through, utility and meter transfers, and warranty cover for structure, waterproofing and fittings through the first year.</p>'],
                        ['id' => 4, 'question' => 'Do you work with landowners outside Dhaka?', 'answer' => '<p>We consider plots in Dhaka and in Chattogram. Share your plot details through the <a href="/landowner">landowner page</a> and the land team will tell you whether it fits.</p>'],
                    ],
                ], spacing: 'lg'),
                DemoBlocks::block(7, 'cta_band', [
                    'heading' => 'Not sure where to start?',
                    'text' => 'Tell us what you need and a sales advisor will suggest projects that fit your budget and timeline.',
                    'primary_cta' => self::link('Talk to our team', '/contact'),
                    'secondary_cta' => self::link('Browse projects', '/projects'),
                    'style' => 'gradient',
                    'image' => null,
                ], background: 'dark', spacing: 'lg'),
            ],
        ];
    }

    /**
     * PG-10, LAY-LANDOWNER: hero (medium) → feature_cards (benefits) →
     * process_steps (5) → rich_text (terms) → project_grid (handed over, 3) →
     * faq → form.
     *
     * @return BuilderPage
     */
    private static function landowner(): array
    {
        return [
            'title' => 'Landowners',
            'description' => 'Partner with Gemcon City Limited on your land through a registered joint-venture agreement with a fixed share of the finished building.',
            'index' => true,
            'blocks' => [
                self::hero(
                    'hero/hero-03',
                    'hero/hero-03-mobile',
                    'For landowners',
                    'Turn your land into a building you share in',
                    'A registered joint-venture agreement, a fixed share of the finished building and updates at every stage.',
                    self::link('Share your plot details', '#enquire'),
                    self::link('How it works', '#how-it-works'),
                ),
                DemoBlocks::block(2, 'feature_cards', [
                    'heading' => 'Why landowners choose us',
                    'columns' => 3,
                    'tilt' => false,
                    'items' => [
                        ['icon' => 'file-check', 'image' => null, 'title' => 'A registered agreement', 'text' => 'Your share is written into a registered contract before any work begins.', 'link' => null],
                        ['icon' => 'banknote', 'image' => null, 'title' => 'No cost to you', 'text' => 'We fund design, approvals and construction. You contribute the land.', 'link' => null],
                        ['icon' => 'shield-check', 'image' => null, 'title' => 'Your title stays safe', 'text' => 'Legal reviews on both sides, and a clear process for handing over your share.', 'link' => null],
                        ['icon' => 'ruler', 'image' => null, 'title' => 'A design that fits the plot', 'text' => 'Our architects maximise the approved floor area while keeping light and access for the neighbours.', 'link' => null],
                        ['icon' => 'hard-hat', 'image' => null, 'title' => 'Visible progress', 'text' => 'Monthly photos and engineer’s notes show exactly how the building is coming along.', 'link' => null],
                        ['icon' => 'key-round', 'image' => null, 'title' => 'On-time handover', 'text' => 'Your share of apartments or floors is handed over on the agreed date.', 'link' => null],
                    ],
                ], spacing: 'lg'),
                self::anchored(DemoBlocks::block(3, 'process_steps', [
                    'heading' => 'From first call to your share of the building',
                    'steps' => [
                        ['title' => 'Share your plot details', 'text' => 'Tell us the location, size and ownership of the land. Our land team replies within three working days.'],
                        ['title' => 'Site and title review', 'text' => 'Engineers visit the plot and our legal team reviews the deeds, mutation and tax records.'],
                        ['title' => 'Agree the terms', 'text' => 'We propose your share of the finished building and the handover schedule, and you take the draft to your own lawyer.'],
                        ['title' => 'Sign and register', 'text' => 'The joint-venture agreement is signed and registered, and the project is submitted for approval.'],
                        ['title' => 'Build and hand over', 'text' => 'We build the project, update you monthly and hand over your share on the agreed date.'],
                    ],
                ], background: 'muted', spacing: 'lg'), 'how-it-works'),
                DemoBlocks::block(4, 'rich_text', [
                    'body' => '<h2>Terms at a glance</h2><p>Every joint venture is negotiated for the plot, but these principles apply to all of them:</p><ul><li>The landowner’s share is a fixed number of apartments or floors, named in the agreement.</li><li>Gemcon City bears the cost of design, approvals, construction and marketing.</li><li>A handover date is part of the agreement, with compensation for delays within our control.</li><li>The land is not mortgaged or sold without the landowner’s written consent.</li></ul><p>Figures and shares on this page are examples for discussion, not an offer. Final terms are set in the registered agreement.</p>',
                    'width' => 'narrow',
                ], spacing: 'lg'),
                DemoBlocks::block(5, 'project_grid', [
                    'heading' => 'Handed over on the agreed date',
                    'projects' => DemoContent::projectCards(lifecycle: 'handed_over', limit: 3),
                    'view_all' => self::link('All handed-over projects', '/projects/status/handed-over'),
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(6, 'faq', [
                    'heading' => 'Landowner questions',
                    'items' => [
                        ['id' => 1, 'question' => 'How big does my plot need to be?', 'answer' => '<p>Plots of five katha and above are usually suitable, depending on the road width and the approved floor-area ratio. Smaller plots can sometimes be combined with neighbouring ones.</p>'],
                        ['id' => 2, 'question' => 'What share of the building do I receive?', 'answer' => '<p>The share depends on the plot, its location and the approved design. We propose a figure after the site and title review, and you can negotiate it with your lawyer.</p>'],
                        ['id' => 3, 'question' => 'How long does a project take?', 'answer' => '<p>Approvals usually take three to four months and construction fourteen to eighteen months for a mid-rise building. The schedule is written into the agreement.</p>'],
                        ['id' => 4, 'question' => 'Can I see projects built on landowners’ plots?', 'answer' => '<p>Yes. Our sales team can arrange visits to handed-over projects and introductions to landowners who have agreed to speak with you.</p>'],
                        ['id' => 5, 'question' => 'What happens if the project is delayed?', 'answer' => '<p>The agreement sets out the compensation for each month of delay within our control, so the risk does not sit with you.</p>'],
                    ],
                ], spacing: 'lg'),
                self::anchored(DemoBlocks::block(7, 'form', [
                    'heading' => 'Share your plot details',
                    'intro' => 'Tell us where your land is and how to reach you. The land team will review it and call you back within three working days.',
                    'form_type' => 'landowner',
                    'side' => '<p>You can also call the land team on the hotline during office hours. Your details are used only to respond to your enquiry.</p>',
                ], background: 'muted', spacing: 'lg'), 'enquire'),
            ],
        ];
    }

    /**
     * PG-11, LAY-NRB: hero (medium) → feature_cards (why buy) → process_steps
     * (remote buying) → rich_text (payment and legal) → testimonials (NRB) →
     * faq → form.
     *
     * @return BuilderPage
     */
    private static function nrb(): array
    {
        return [
            'title' => 'Non-resident Bangladeshis',
            'description' => 'Buy a home in Bangladesh from abroad with Gemcon City Limited: video site visits, monthly progress photos and a clear remote buying process for NRBs.',
            'index' => true,
            'blocks' => [
                self::hero(
                    'hero/hero-02',
                    'hero/hero-02-mobile',
                    'For non-resident Bangladeshis',
                    'Buy a home in Bangladesh from wherever you live',
                    'Video site visits, monthly progress reports and a legal process that works across time zones.',
                    self::link('Talk to the NRB desk', '#enquire'),
                    self::link('How buying works', '#how-it-works'),
                ),
                DemoBlocks::block(2, 'feature_cards', [
                    'heading' => 'Why NRBs buy with us',
                    'columns' => 3,
                    'tilt' => false,
                    'items' => [
                        ['icon' => 'phone', 'image' => null, 'title' => 'A dedicated NRB desk', 'text' => 'One advisor answers your questions by phone, WhatsApp or email in your time zone.', 'link' => null],
                        ['icon' => 'compass', 'image' => null, 'title' => 'Video site visits', 'text' => 'An engineer walks the plot or the building with you on a live call and answers questions as you go.', 'link' => null],
                        ['icon' => 'scale', 'image' => null, 'title' => 'Clear legal steps', 'text' => 'Agreements are explained in plain language, and power of attorney is handled through your embassy or a local lawyer.', 'link' => null],
                        ['icon' => 'hard-hat', 'image' => null, 'title' => 'Monthly progress reports', 'text' => 'Photos and engineer’s notes arrive every month, so you can follow the build from abroad.', 'link' => null],
                        ['icon' => 'banknote', 'image' => null, 'title' => 'Payments from abroad', 'text' => 'Pay through banking channels in foreign currency, with receipts issued for every instalment.', 'link' => null],
                        ['icon' => 'key-round', 'image' => null, 'title' => 'Handover without the trip', 'text' => 'Authorise a family member to take the keys, or let our after-sales team manage the unit until you arrive.', 'link' => null],
                    ],
                ], spacing: 'lg'),
                self::anchored(DemoBlocks::block(3, 'process_steps', [
                    'heading' => 'Buying remotely, step by step',
                    'steps' => [
                        ['title' => 'Tell us what you need', 'text' => 'Share your budget, preferred area and timeline with the NRB desk.'],
                        ['title' => 'Take a video visit', 'text' => 'Join a live walk-through of the site or the show apartment, with an engineer on the call.'],
                        ['title' => 'Reserve your unit', 'text' => 'Pay the booking amount through banking channels and receive a receipt and the payment schedule.'],
                        ['title' => 'Sign the agreement', 'text' => 'Sign through a power of attorney or in person, with our legal team available to explain each clause.'],
                        ['title' => 'Follow the build', 'text' => 'Monthly progress reports and photos keep you informed until handover.'],
                    ],
                ], background: 'muted', spacing: 'lg'), 'how-it-works'),
                DemoBlocks::block(4, 'rich_text', [
                    'body' => '<h2>Payments and legal notes</h2><p>Rules for buying property from abroad change from time to time. Check the current position with your bank and a Bangladeshi lawyer; our team can explain the documents involved.</p><ul><li>Payments are made through authorised banking channels, in foreign currency or taka as the rules allow.</li><li>A power of attorney signed abroad is attested by the Bangladesh embassy or consulate.</li><li>Your passport and a recent photograph are needed for the agreement and registration.</li></ul><p>This page is general information, not legal advice.</p>',
                    'width' => 'narrow',
                ], spacing: 'lg'),
                DemoBlocks::block(5, 'testimonials', [
                    'heading' => 'NRB buyers in their own words',
                    'layout' => 'carousel',
                    'items' => DemoContent::testimonials(nrbOnly: true),
                ], background: 'muted'),
                DemoBlocks::block(6, 'faq', [
                    'heading' => 'NRB questions',
                    'items' => [
                        ['id' => 1, 'question' => 'Can I buy without visiting Bangladesh?', 'answer' => '<p>Yes. Many of our NRB buyers complete the whole process remotely, using video visits, a power of attorney and bank transfers. You are welcome to visit whenever it suits you.</p>'],
                        ['id' => 2, 'question' => 'How do I pay from abroad?', 'answer' => '<p>Through authorised banking channels. The NRB desk will share the account details and the receipt process, and explain how your bank should describe the transfer.</p>'],
                        ['id' => 3, 'question' => 'Who can sign on my behalf?', 'answer' => '<p>A relative or lawyer you name in a power of attorney attested by the Bangladesh embassy or consulate in your country.</p>'],
                        ['id' => 4, 'question' => 'Can someone else collect the keys?', 'answer' => '<p>Yes, an authorised family member can take the keys at handover, or our after-sales team can look after the unit until you arrive.</p>'],
                    ],
                ], spacing: 'lg'),
                self::anchored(DemoBlocks::block(7, 'form', [
                    'heading' => 'Talk to the NRB desk',
                    'intro' => 'Tell us where you live and what you are looking for. An advisor will reply within one working day in your time zone.',
                    'form_type' => 'nrb',
                    'side' => '<p>Prefer a call? Message the hotline on WhatsApp and we will arrange a time that works for you.</p>',
                ], background: 'muted', spacing: 'lg'), 'enquire'),
            ],
        ];
    }

    /**
     * PG-25 demo custom page (indexed): LAY-CUSTOM with free block choice.
     *
     * @return BuilderPage
     */
    private static function sustainability(): array
    {
        return [
            'title' => 'Sustainability',
            'description' => 'How Gemcon City Limited designs for daylight, ventilation, green cover and lower running costs in every project.',
            'index' => true,
            'blocks' => [
                DemoBlocks::block(1, 'page_header', [
                    'title' => 'Building lightly on the city',
                    'intro' => 'Daylight, cross-ventilation and green cover cost less to include at the start than to retrofit later.',
                    'image' => DemoMedia::image('locations/locations-04'),
                    'breadcrumbs' => Breadcrumbs::to('Sustainability'),
                ], spacing: 'none'),
                DemoBlocks::block(2, 'rich_text', [
                    'body' => '<p>A building uses resources for decades after it is finished. We make the choices that reduce that use during design, when they are cheapest, and publish them on each project page.</p><h2>Our approach</h2><p>Every project is reviewed against a short checklist covering orientation, shading, ventilation, water and waste. Where a project falls short, the review explains why and what was done instead.</p>',
                    'width' => 'narrow',
                ], spacing: 'lg'),
                DemoBlocks::block(3, 'feature_cards', [
                    'heading' => 'What we design for',
                    'columns' => 3,
                    'tilt' => false,
                    'items' => [
                        ['icon' => 'sun', 'image' => null, 'title' => 'Daylight and air', 'text' => 'Living rooms face the open side of the plot and every unit is tested for cross-ventilation.', 'link' => null],
                        ['icon' => 'trees', 'image' => null, 'title' => 'Green cover', 'text' => 'Trees, planters and rooftop gardens are part of the approved design, not an afterthought.', 'link' => null],
                        ['icon' => 'leaf', 'image' => null, 'title' => 'Lower running costs', 'text' => 'Efficient lighting, water recycling for gardens and shaded façades reduce bills for owners.', 'link' => null],
                    ],
                ], background: 'muted', spacing: 'lg'),
                DemoBlocks::block(4, 'image_text', [
                    'heading' => 'Measured on site, not only on paper',
                    'body' => '<p>Our engineers record water and energy use in the first year of occupancy for selected projects, and the results feed back into the next design.</p>',
                    'image' => DemoMedia::image('progress/progress-02'),
                    'image_side' => 'left',
                    'cta' => null,
                    'reveal' => 'mask',
                ], spacing: 'lg'),
                DemoBlocks::block(5, 'cta_band', [
                    'heading' => 'Visit a project and see the details.',
                    'text' => 'Book a site visit and ask our engineers how each building is designed.',
                    'primary_cta' => self::link('Browse projects', '/projects'),
                    'secondary_cta' => self::link('Contact us', '/contact'),
                    'style' => 'gradient',
                    'image' => null,
                ], background: 'dark', spacing: 'lg'),
            ],
        ];
    }

    /**
     * PG-25 demo custom page with `noindex` set (the per-page setting).
     *
     * @return BuilderPage
     */
    private static function buyerGuide(): array
    {
        return [
            'title' => 'Buyer’s guide',
            'description' => 'A checklist of the documents and questions to cover before you buy an apartment, commercial space or plot in Bangladesh.',
            'index' => false,
            'blocks' => [
                DemoBlocks::block(1, 'page_header', [
                    'title' => 'Before you buy: a short checklist',
                    'intro' => 'The documents and questions that protect you, whoever you buy from.',
                    'image' => null,
                    'breadcrumbs' => Breadcrumbs::to('Buyer’s guide'),
                ]),
                DemoBlocks::block(2, 'rich_text', [
                    'body' => '<h2>Documents to ask for</h2><ol><li>The deed and the mutation (<em>khatian</em>) records for the land.</li><li>The RAJUK approval letter and the approved drawings.</li><li>The allotment agreement, including the payment schedule and handover date.</li></ol><h2>Questions to ask</h2><p>Who holds the title, what happens if the project is delayed, and what is included in the price? A reliable developer answers all three in writing.</p>',
                    'width' => 'narrow',
                ], spacing: 'lg'),
                DemoBlocks::block(3, 'faq', [
                    'heading' => 'Quick answers',
                    'items' => [
                        ['id' => 1, 'question' => 'Is the booking amount refundable?', 'answer' => '<p>It depends on the terms in your booking letter. Ask for the cooling-off period and refund conditions in writing before you pay.</p>'],
                        ['id' => 2, 'question' => 'How do I check an approval number?', 'answer' => '<p>Ask the developer for the approval letter and compare its number with the one on the project page and the RAJUK records.</p>'],
                    ],
                ], background: 'muted', spacing: 'lg'),
            ],
        ];
    }

    /**
     * The medium hero of the landowner and NRB pages (HeroBlockData): no
     * search widget, a primary and a secondary CTA.
     *
     * @param  array{label: string, url: string, new_tab: bool}  $primary
     * @param  array{label: string, url: string, new_tab: bool}  $secondary
     * @return array<string, mixed>
     */
    private static function hero(string $image, string $mobile, string $eyebrow, string $headline, string $subheadline, array $primary, array $secondary): array
    {
        return DemoBlocks::block(1, 'hero', [
            'mode' => 'single',
            'media_type' => 'image',
            'image' => DemoMedia::image($image),
            'mobile_image' => DemoMedia::image($mobile),
            'video' => null,
            'slides' => [],
            'autoplay' => false,
            'eyebrow' => $eyebrow,
            'headline' => $headline,
            'subheadline' => $subheadline,
            'primary_cta' => $primary,
            'secondary_cta' => $secondary,
            'overlay_opacity' => 55,
            'overlay_style' => 'solid',
            'text_align' => 'left',
            'show_search' => false,
            'height' => 'medium',
            'search' => null,
        ], spacing: 'none');
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private static function anchored(array $block, string $anchor): array
    {
        return [...$block, 'anchor_id' => $anchor];
    }

    /**
     * @return array{label: string, url: string, new_tab: bool}
     */
    private static function link(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url, 'new_tab' => false];
    }

    private static function privacyBody(): string
    {
        return '<p><em>Draft text for the client’s review. Replace it with the wording approved by your legal adviser before launch.</em></p>'
            .'<p>Gemcon City Limited (“we”, “us”) respects your privacy. This policy explains what personal information we collect through this website, why we collect it and the choices you have.</p>'
            .'<h2>Who we are</h2>'
            .'<p>Gemcon City Limited is a real estate developer registered in Bangladesh. We are responsible for the personal information collected through this website. You can reach us using the details on the <a href="/contact">contact page</a>.</p>'
            .'<h2>Information we collect</h2>'
            .'<p>We collect only what we need to respond to you:</p>'
            .'<ul><li>Details you give us in an enquiry, site-visit request, brochure request or application: your name, phone number, email address, the project you are interested in and your message.</li><li>Documents you upload, such as a CV or plot papers.</li><li>Technical information such as your browser, device type and the pages you visit, collected through cookies if you allow them.</li></ul>'
            .'<h2>How we use your information</h2>'
            .'<p>We use it to answer your enquiry, arrange visits, send the documents you ask for, assess job applications and improve this website. We contact you about other projects only if you have agreed to it, and you can stop at any time.</p>'
            .'<h2>Cookies and analytics</h2>'
            .'<p>Essential cookies keep the site working. Analytics and marketing cookies are set only after you accept them in the cookie banner, and you can change your choice at any time from the link in the footer.</p>'
            .'<h2>Who we share it with</h2>'
            .'<p>We do not sell personal information. We share it only with service providers who help us run the website and our sales operations, under agreements that require them to protect it, and where the law requires it.</p>'
            .'<h2>How long we keep it</h2>'
            .'<p>Enquiries are kept for as long as needed to follow up and for a limited period afterwards. Job applications are deleted within twelve months unless you ask us to keep them for future roles.</p>'
            .'<h2>Your rights</h2>'
            .'<p>You may ask to see the information we hold about you, correct it, or ask us to erase it. Write to us using the contact details below and we will reply within a reasonable time.</p>'
            .'<h2>Security</h2>'
            .'<p>We use access controls, encrypted connections and regular backups to protect your information. No online service is completely secure, so please avoid sending sensitive documents by email.</p>'
            .'<h2>Contact</h2>'
            .'<p>For any question about this policy, contact us through the <a href="/contact">contact page</a>.</p>';
    }

    private static function termsBody(): string
    {
        return '<p><em>Draft text for the client’s review. Replace it with the wording approved by your legal adviser before launch.</em></p>'
            .'<p>These terms apply when you use the Gemcon City Limited website. By using it you agree to them.</p>'
            .'<h2>Using this website</h2>'
            .'<p>You may browse the site, use the enquiry forms and download the documents we publish for personal, non-commercial use. You must not misuse the site, attempt to disrupt it or submit information that is false or belongs to someone else.</p>'
            .'<h2>Project information and prices</h2>'
            .'<p>Descriptions, areas, images, floor plans, amenities and handover dates on this website are indicative and may change as designs and approvals are finalised. Images and illustrations are for illustration only. Prices shown are starting prices, can change without notice and are not an offer capable of acceptance.</p>'
            .'<h2>Bookings and agreements</h2>'
            .'<p>A booking becomes binding only when you sign an allotment or sale agreement with Gemcon City Limited. The agreement, not this website, sets out the price, payment schedule, handover date and your rights.</p>'
            .'<h2>Enquiries and communications</h2>'
            .'<p>When you send an enquiry you agree that we may contact you by phone, WhatsApp or email about it. How we handle your information is described in our <a href="/privacy-policy">privacy policy</a>.</p>'
            .'<h2>Intellectual property</h2>'
            .'<p>The text, images, logos, drawings and designs on this site belong to Gemcon City Limited or its licensors. You may not copy or reuse them for commercial purposes without our written permission.</p>'
            .'<h2>Links to other websites</h2>'
            .'<p>This site may link to websites we do not control. We are not responsible for their content or practices.</p>'
            .'<h2>Limits of liability</h2>'
            .'<p>We take care to keep the website accurate and available, but we do not promise that it is free of errors or interruptions. To the extent the law allows, we are not liable for losses arising from your use of the website.</p>'
            .'<h2>Changes to these terms</h2>'
            .'<p>We may update these terms from time to time. The date at the top of this page shows when they last changed.</p>'
            .'<h2>Governing law</h2>'
            .'<p>These terms are governed by the laws of Bangladesh, and the courts of Dhaka have jurisdiction over any dispute.</p>';
    }
}
