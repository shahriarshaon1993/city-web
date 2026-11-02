<?php

namespace App\Support\Demo;

use App\Support\Seo\Breadcrumbs;

/**
 * Demo data for the content blocks of 6.1 (page_header, rich_text, image_text,
 * feature_cards, process_steps, timeline, tabbed_pillars, quote,
 * parallax_band) in the DM-BLOCK shape of resources/js/types/blocks.ts.
 * Placeholder copy; the builder pages of 6.3 reuse these blocks. Deleted with
 * DemoContent in 16.4.
 */
final class DemoBlocks
{
    private const string RICH_BODY = '<p>Gemcon City was founded to build homes and workplaces that hold their value. Every project starts with the land: we check the title, the zoning and the access before a single drawing is made, so that what we promise buyers is what we can deliver.</p>'
        .'<p>Our architects work with structural and services engineers from the first sketch. Designs are approved by RAJUK before launch, and the approval numbers are published on each project page so that any buyer can verify them.</p>'
        .'<h2>How we choose land</h2>'
        .'<p>We buy or develop only plots with a clear chain of ownership and a road wide enough for emergency access. A site must also pass three checks:</p>'
        .'<ul><li>Soil tests and a structural survey by an independent engineer.</li><li>A drainage and flooding review for the surrounding area.</li><li>Distance to schools, hospitals and public transport.</li></ul>'
        .'<h3>Landowner partnerships</h3>'
        .'<p>Where the land belongs to a family or a company, we build under a joint-venture agreement. The landowner receives a fixed share of the finished building, written into a registered contract before work begins. <a href="/landowner">Read how the landowner programme works</a>.</p>'
        .'<blockquote>A home is the largest purchase most families will ever make. We treat the paperwork with the same care as the concrete.</blockquote>'
        .'<h2>After the keys change hands</h2>'
        .'<p>Handover is the start of a longer relationship. Our after-sales team stays with each building through its first year, and owners’ associations can call on us for advice on maintenance and sinking funds.</p>'
        .'<ol><li>Snagging walk-through with the project engineer.</li><li>Utility connections and meter transfers.</li><li>Warranty cover for structure, waterproofing and fittings.</li></ol>'
        .'<hr>'
        .'<table><thead><tr><th>Stage</th><th>Typical time</th></tr></thead><tbody><tr><td>Land due diligence</td><td>6–8 weeks</td></tr><tr><td>Design approval</td><td>3–4 months</td></tr><tr><td>Structure</td><td>14–18 months</td></tr></tbody></table>';

    /**
     * All nine blocks for `/styleguide/blocks`. `$header` picks the page
     * header (`image` = photo under the transparent header, `plain`).
     *
     * @return list<array<string, mixed>>
     */
    public static function styleguide(string $header = 'image'): array
    {
        return [
            self::pageHeader($header),
            self::block(2, 'rich_text', [
                'body' => self::RICH_BODY,
                'width' => 'narrow',
            ]),
            self::block(3, 'image_text', [
                'heading' => 'Designed around daylight and air',
                'body' => '<p>Every floor plan is tested for cross-ventilation and natural light before it is approved. Living rooms face the open side of the plot, and service areas are kept to the inner core.</p><p>The result is a building that stays comfortable in the monsoon and the heat, with less need for artificial cooling.</p>',
                'image' => DemoMedia::image('interiors/interiors-02'),
                'image_side' => 'right',
                'cta' => ['label' => 'See our projects', 'url' => '/projects', 'new_tab' => false],
                'reveal' => 'mask',
            ], background: 'muted'),
            self::block(4, 'image_text', [
                'heading' => 'Engineered for the long run',
                'body' => '<p>Foundations and frames are designed to current Bangladesh National Building Code requirements, and every pour is tested on site. Our engineers publish inspection stages so buyers can follow progress.</p>',
                'image' => DemoMedia::image('progress/progress-02'),
                'image_side' => 'left',
                'cta' => null,
                'reveal' => 'none',
            ]),
            self::block(5, 'feature_cards', [
                'heading' => 'Why families choose Gemcon City',
                'columns' => 3,
                'tilt' => false,
                'items' => [
                    ['icon' => 'shield-check', 'image' => null, 'title' => 'Clear titles', 'text' => 'Every plot is checked by our legal team, and the documents are available before you book.', 'link' => null],
                    ['icon' => 'ruler', 'image' => null, 'title' => 'RAJUK-approved designs', 'text' => 'Approval numbers are printed on each project page so you can verify them yourself.', 'link' => ['label' => 'How we build', 'url' => '/about', 'new_tab' => false]],
                    ['icon' => 'key-round', 'image' => null, 'title' => 'On-time handover', 'text' => 'Handover dates are written into the agreement, with a clear remedy if we miss them.', 'link' => null],
                    ['icon' => 'handshake', 'image' => null, 'title' => 'Landowner partnerships', 'text' => 'Joint-venture agreements with a fixed share of the finished building for the landowner.', 'link' => null],
                    ['icon' => 'hammer', 'image' => null, 'title' => 'Tested materials', 'text' => 'Steel and concrete are tested on site at every pour, and results are shared with owners.', 'link' => null],
                    ['icon' => 'users', 'image' => null, 'title' => 'After-sales care', 'text' => 'A dedicated team stays with each building through its first year of occupancy.', 'link' => null],
                ],
            ], spacing: 'lg'),
            self::block(6, 'process_steps', [
                'heading' => 'From first visit to your front door',
                'steps' => [
                    ['title' => 'Choose a project', 'text' => 'Browse our projects by location, size and budget, and shortlist the ones that fit.'],
                    ['title' => 'Visit the site', 'text' => 'Book a visit and walk the plot with a sales advisor and, where built, the show apartment.'],
                    ['title' => 'Book your unit', 'text' => 'Reserve your unit with a booking amount and receive the payment schedule in writing.'],
                    ['title' => 'Sign the agreement', 'text' => 'Review the allotment agreement with our legal team, then sign and register it.'],
                    ['title' => 'Follow construction', 'text' => 'Receive monthly progress photos and engineer’s notes until the building is complete.'],
                    ['title' => 'Move in', 'text' => 'Join the snagging walk-through, collect your keys and meet the after-sales team.'],
                ],
            ], background: 'muted', spacing: 'lg'),
            self::block(7, 'timeline', [
                'heading' => 'Two decades of building',
                'items' => [
                    ['year' => 2004, 'title' => 'Founded in Dhaka', 'text' => 'Gemcon City delivers its first residential building of twelve apartments in Dhanmondi.', 'image' => DemoMedia::image('projects/projects-04')],
                    ['year' => 2010, 'title' => 'First commercial tower', 'text' => 'A fourteen-storey office building opens in Motijheel with RAJUK approval.', 'image' => null],
                    ['year' => 2016, 'title' => 'Landowner programme', 'text' => 'The joint-venture model launches, with registered agreements and a fixed share for landowners.', 'image' => DemoMedia::image('projects/projects-02')],
                    ['year' => 2021, 'title' => 'Progress reporting', 'text' => 'Monthly construction updates with photos become standard for every ongoing project.', 'image' => null],
                    ['year' => 2025, 'title' => 'Fifty projects handed over', 'text' => 'More than two thousand families now live in Gemcon City buildings.', 'image' => DemoMedia::image('projects/projects-07')],
                ],
            ], spacing: 'lg'),
            self::block(8, 'tabbed_pillars', [
                'heading' => 'What we stand for',
                'tabs' => [
                    ['label' => 'Integrity', 'title' => 'Say what we will do, then do it', 'text' => 'Prices, areas and handover dates are written down before you book. When something changes, we tell you first and explain why.', 'image' => DemoMedia::image('interiors/interiors-01')],
                    ['label' => 'Craft', 'title' => 'Details that last', 'text' => 'We specify fittings and finishes for fifty years of use, not for the brochure. Joinery, waterproofing and services are inspected at every stage.', 'image' => DemoMedia::image('interiors/interiors-03')],
                    ['label' => 'Community', 'title' => 'Buildings that belong to their streets', 'text' => 'Setbacks, green cover and shared spaces are designed with the neighbourhood in mind, so a new building improves the street it stands on.', 'image' => DemoMedia::image('locations/locations-02')],
                ],
            ], background: 'muted', spacing: 'lg'),
            self::block(9, 'quote', [
                'quote' => 'We booked off the plan and wondered if the building would match the brochure. The monthly photos told us it would, and on handover day it did.',
                'person' => 'Nusrat Jahan',
                'role' => 'Apartment owner, Uttara',
                'photo' => null,
            ], spacing: 'lg'),
            self::block(10, 'parallax_band', [
                'image' => DemoMedia::image('hero/hero-02'),
                'heading' => 'Towers that sit lightly on the skyline',
                'text' => 'Set back from the street, wrapped in greenery and finished to last.',
                'height' => 'md',
                'parallax' => true,
            ], spacing: 'none'),
            self::block(11, 'feature_cards', [
                'heading' => 'Inside a Gemcon City home',
                'columns' => 4,
                'tilt' => true,
                'items' => [
                    ['icon' => null, 'image' => DemoMedia::image('interiors/interiors-01'), 'title' => 'Living spaces', 'text' => 'Wide rooms facing the open side of the plot.', 'link' => null],
                    ['icon' => null, 'image' => DemoMedia::image('interiors/interiors-02'), 'title' => 'Kitchens', 'text' => 'Planned around ventilation and storage.', 'link' => null],
                    ['icon' => null, 'image' => DemoMedia::image('interiors/interiors-03'), 'title' => 'Bedrooms', 'text' => 'Quiet, shaded and cross-ventilated.', 'link' => null],
                    ['icon' => null, 'image' => DemoMedia::image('interiors/interiors-04'), 'title' => 'Shared spaces', 'text' => 'Gardens, lobbies and rooftops for owners.', 'link' => ['label' => 'Amenities', 'url' => '/projects', 'new_tab' => false]],
                ],
            ], background: 'muted'),
            self::block(12, 'feature_cards', [
                'heading' => 'Two ways to start',
                'columns' => 2,
                'tilt' => false,
                'items' => [
                    ['icon' => 'map-pin', 'image' => null, 'title' => 'Buy a home or a plot', 'text' => 'Choose from apartments, commercial spaces and land across Dhaka and beyond.', 'link' => ['label' => 'View projects', 'url' => '/projects', 'new_tab' => false]],
                    ['icon' => 'landmark', 'image' => null, 'title' => 'Partner on your land', 'text' => 'Talk to the land team about a joint venture with a registered, fixed-share agreement.', 'link' => ['label' => 'For landowners', 'url' => '/landowner', 'new_tab' => false]],
                ],
            ]),
            self::block(13, 'rich_text', [
                'body' => self::RICH_BODY,
                'width' => 'wide',
            ], background: 'muted'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function pageHeader(string $variant): array
    {
        $image = $variant === 'plain' ? null : DemoMedia::image('hero/hero-03');

        return self::block(1, 'page_header', [
            'title' => 'Building Dhaka’s next landmarks',
            'intro' => 'A developer of apartments, workplaces and plots with clear titles, approved designs and handovers on the dates we promise.',
            'image' => $image,
            'breadcrumbs' => Breadcrumbs::to('Content blocks', [['Styleguide', '/styleguide']]),
        ], spacing: $image === null ? 'md' : 'none');
    }

    /**
     * DM-BLOCK envelope (CMS-PB-001).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function block(int $id, string $type, array $data, string $background = 'default', string $spacing = 'md'): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'is_visible' => true,
            'anchor_id' => null,
            'background' => $background,
            'spacing' => $spacing,
            'animate' => true,
            'data' => $data,
        ];
    }
}
