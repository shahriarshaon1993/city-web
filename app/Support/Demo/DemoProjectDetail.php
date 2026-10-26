<?php

namespace App\Support\Demo;

use App\Support\Format;
use App\Support\Search\ProjectFilters;
use App\Support\Seo\Breadcrumbs;

/**
 * Part A project detail (PG-04, DEC-29): one DemoProjects row expanded into
 * the ProjectDetailResource shape of types/project.ts — gallery, facts,
 * amenities, unit types, highlights; the 4.3 sections come from
 * DemoProjectSections. Unit types, gallery and facts are
 * derived from the row so all 26 demo projects have a page; the wording is
 * placeholder text. Hidden prices never leave here (AC-PROJ-01): a unit
 * price is sent only when the project and the unit both show prices.
 * 13.3 replaces this with ProjectDetailResource (same shape); deleted in 16.4.
 *
 * @phpstan-import-type DemoProject from DemoProjects
 * @phpstan-import-type ProjectCardData from DemoProjects
 * @phpstan-import-type MediaImage from DemoMedia
 *
 * @phpstan-type GalleryItemData array{id: int, category: string, category_label: string, image: MediaImage|null}
 * @phpstan-type ProjectDetailData array{id: int, slug: string, url: string, name: string, segment: string, segment_label: string, lifecycle: string, lifecycle_label: string, booking_state: string, booking_label: string|null, is_featured: bool, location: array{area: string, city: string, url: string}, cover: MediaImage|null, size_min_sft: int|null, size_max_sft: int|null, beds_min: int|null, beds_max: int|null, baths_min: int|null, baths_max: int|null, land_katha: int|float|null, price_from_bdt: int|null, price_note: string|null, tagline: string, segment_url: string|null, handover_date: string|null, gallery: list<GalleryItemData>, overview: string, design_narrative: string|null, highlights: list<array{id: int, title: string, text: string, image: MediaImage|null}>, facts: array<string, mixed>, custom_facts: list<array{label: string, value: string}>, amenities: list<array<string, mixed>>, unit_types: list<array<string, mixed>>, breadcrumbs: list<array{label: string, url: string|null}>, video: array<string, mixed>|null, virtual_tour_url: string|null, progress: list<array<string, mixed>>, coordinates: array{lat: float, lng: float}, address: string|null, nearby: list<array<string, mixed>>, design_team: list<array{role: string, role_label: string, name: string}>, documents: list<array<string, mixed>>, faq: list<array{id: int, question: string, answer: string}>, similar: list<ProjectCardData>, enquiry: array{heading: string, whatsapp: string|null, whatsapp_text: string}}
 */
final class DemoProjectDetail
{
    public const array GALLERY_LABELS = ['exterior' => 'Exterior', 'interior' => 'Interior', 'amenity' => 'Amenity', 'site' => 'Site', 'night' => 'Night'];

    public const array AVAILABILITY_LABELS = ['available' => 'Available', 'limited' => 'Limited', 'sold_out' => 'Sold out'];

    public const array AMENITY_CATEGORY_LABELS = ['building' => 'Building', 'security' => 'Safety & security', 'lifestyle' => 'Lifestyle', 'utility' => 'Utilities'];

    /** DM-AMEN rows: slug => [name, Lucide icon (lib/icons.ts allowlist), category]. */
    private const array AMENITIES = [
        'lifts' => ['Passenger lifts', 'arrow-up-down', 'building'],
        'lobby' => ['Double-height reception lobby', 'concierge-bell', 'building'],
        'parking' => ['Basement car parking', 'square-parking', 'building'],
        'community-hall' => ['Community hall', 'users', 'building'],
        'cctv' => ['CCTV in common areas', 'cctv', 'security'],
        'guards' => ['24-hour security', 'shield-check', 'security'],
        'fire' => ['Fire detection and hydrants', 'fire-extinguisher', 'security'],
        'intercom' => ['Intercom to every unit', 'phone', 'security'],
        'rooftop' => ['Rooftop garden', 'trees', 'lifestyle'],
        'pool' => ['Swimming pool', 'waves-ladder', 'lifestyle'],
        'gym' => ['Gym', 'dumbbell', 'lifestyle'],
        'play' => ["Children's play area", 'baby', 'lifestyle'],
        'prayer' => ['Prayer room', 'moon', 'lifestyle'],
        'generator' => ['Full generator back-up', 'zap', 'utility'],
        'water' => ['Water treatment plant', 'droplets', 'utility'],
        'gas' => ['Gas line', 'flame', 'utility'],
        'solar' => ['Solar common-area lighting', 'sun', 'utility'],
        'internet' => ['Fibre internet ready', 'wifi', 'utility'],
        'retail' => ['Ground-floor retail', 'store', 'building'],
        'boundary' => ['Boundary wall and gated entry', 'fence', 'security'],
        'roads' => ['Paved internal roads', 'trees', 'utility'],
    ];

    private const array SEGMENT_AMENITIES = [
        'residential' => ['lifts', 'lobby', 'parking', 'community-hall', 'cctv', 'guards', 'fire', 'intercom', 'rooftop', 'pool', 'gym', 'play', 'prayer', 'generator', 'water', 'gas', 'solar'],
        'commercial' => ['lifts', 'lobby', 'parking', 'cctv', 'guards', 'fire', 'prayer', 'generator', 'water', 'internet'],
        'mixed_use' => ['lifts', 'lobby', 'parking', 'retail', 'cctv', 'guards', 'fire', 'intercom', 'rooftop', 'gym', 'prayer', 'generator', 'water', 'gas'],
        'land' => ['boundary', 'guards', 'roads', 'water'],
    ];

    /** Interior shots and amenity shots used by every building project. */
    private const array INTERIORS = ['interiors/interiors-01', 'interiors/interiors-02', 'interiors/interiors-03', 'interiors/interiors-04'];

    private const array FACINGS = ['South', 'North-east', 'East', 'South-west'];

    /**
     * PG-04 section headings, tab labels and enquiry texts (template copy,
     * 16.x moves it to Settings); `{project}` is replaced by the page.
     *
     * @return array<string, mixed>
     */
    public static function copy(): array
    {
        return [
            'overview_heading' => 'Overview',
            'overview_tab' => 'Overview',
            'facts_heading' => 'At a glance',
            'facts_tab' => 'At a glance',
            'amenities_heading' => 'Amenities',
            'amenities_tab' => 'Amenities',
            'units_heading' => 'Unit types and floor plans',
            'units_tab' => 'Plans',
            'gallery_heading' => 'Gallery',
            'gallery_tab' => 'Gallery',
            'price_from_label' => 'Starting from',
            'video_heading' => 'Video and virtual tour',
            'video_tab' => 'Video',
            'tour_label' => 'Take the virtual tour',
            'team_heading' => 'Design team',
            'progress_heading' => 'Construction progress',
            'progress_tab' => 'Progress',
            'progress_timeline_heading' => 'All updates',
            'location_heading' => 'Location',
            'location_tab' => 'Location',
            'nearby_heading' => 'Nearby',
            'documents_heading' => 'Brochure and documents',
            'documents_tab' => 'Documents',
            'faq_heading' => 'Questions and answers',
            'similar_heading' => 'Similar projects',
            'price_on_request' => 'Price on request',
            'sales_name' => 'Gemcon sales team',
            'enquire_title' => 'Enquire about {project}',
            'enquire_text' => 'Tell us what you are looking for and our sales team will call you back within one working day.',
            'visit_title' => 'Book a site visit to {project}',
            'visit_text' => 'Pick a day and time that suits you; our sales team confirms the visit by phone. Video visits are available for buyers abroad.',
            'brochure_title' => 'Download the {project} brochure',
            'brochure_text' => 'Leave your name and phone number and the download starts at once.',
            'dialog_alternative' => 'Prefer to talk now?',
            'progress_stages' => DemoContent::progressStages(),
        ];
    }

    /**
     * ProjectDetailResource shape (ProjectDetail in types/project.ts), or null for an unknown slug.
     *
     * @return ProjectDetailData|null
     */
    public static function find(string $slug): ?array
    {
        $row = array_find(DemoProjects::rows(), fn (array $row): bool => $row['slug'] === $slug);

        return $row === null ? null : self::detail($row);
    }

    /**
     * Absolute 1200×630 crop of the cover (SEO-ONP-006).
     */
    public static function ogImage(string $slug): ?string
    {
        $row = array_find(DemoProjects::rows(), fn (array $row): bool => $row['slug'] === $slug);

        return $row === null ? null : DemoMedia::ogImage($row['image']);
    }

    /**
     * @param  DemoProject  $row
     * @return ProjectDetailData
     */
    private static function detail(array $row): array
    {
        $card = DemoProjects::card($row);
        $area = DemoProjects::area($row['area']);
        $units = self::unitTypes($row);
        $gallery = self::gallery($row);
        $shownPrices = array_filter(array_column($units, 'price_bdt'));
        $facts = self::facts($row, $area);

        return [
            ...$card,
            'tagline' => $row['tagline'],
            'segment_url' => ProjectFilters::landingUrl('segment', $row['segment']),
            'location' => [
                ...$card['location'],
                'url' => ProjectFilters::fromQuery(['area' => [$row['area']]], DemoProjectSearch::areaSlugs())->url(false),
            ],
            'handover_date' => $row['handover'] === null ? null : $row['handover'].'-01',
            // Computed from the shown unit types (FR-PROJ-009); land has none.
            'price_from_bdt' => $row['price_visible'] ? ($shownPrices === [] ? $row['price'] : min($shownPrices)) : null,
            'cover' => $gallery[0]['image'],
            'gallery' => $gallery,
            'overview' => self::overview($row, $area),
            'design_narrative' => null,
            'highlights' => self::highlights($row),
            'facts' => $facts,
            'custom_facts' => self::customFacts($row),
            'amenities' => array_map(
                fn (string $slug, int $index): array => [
                    'id' => $index + 1,
                    'slug' => $slug,
                    'name' => self::AMENITIES[$slug][0],
                    'icon' => self::AMENITIES[$slug][1],
                    'category' => self::AMENITIES[$slug][2],
                    'category_label' => self::AMENITY_CATEGORY_LABELS[self::AMENITIES[$slug][2]],
                ],
                self::SEGMENT_AMENITIES[$row['segment']],
                array_keys(self::SEGMENT_AMENITIES[$row['segment']]),
            ),
            'unit_types' => $units,
            'breadcrumbs' => Breadcrumbs::to($row['name'], [['Projects', ProjectFilters::basePath()]]),
            'video' => DemoProjectSections::video($row),
            'virtual_tour_url' => DemoProjectSections::virtualTour($row),
            'progress' => DemoProjectSections::progress($row),
            'coordinates' => DemoProjectSections::coordinates($row),
            'address' => $facts['address'],
            'nearby' => DemoProjectSections::nearby($row),
            'design_team' => DemoProjectSections::designTeam($row),
            'documents' => DemoProjectSections::documents($row),
            'faq' => DemoProjectSections::faq($row),
            'similar' => DemoProjectSections::similar($row),
            'enquiry' => DemoProjectSections::enquiry($row),
        ];
    }

    /**
     * DM-UNIT rows: residential types interpolate the row's size/bed ranges,
     * commercial ones are suites to full floors, mixed-use adds a shop, land has none.
     *
     * @param  DemoProject  $row
     * @return list<array<string, mixed>>
     */
    private static function unitTypes(array $row): array
    {
        if ($row['segment'] === 'land' || $row['size'][0] === null) {
            return [];
        }

        [$sizeMin, $sizeMax] = [$row['size'][0], $row['size'][1] ?? $row['size'][0]];
        $specs = [];

        if ($row['segment'] === 'commercial') {
            $specs = [
                ['Retail unit', $sizeMin, null, null, 'plans/plans-06', 'Ground floor'],
                ['Office suite', self::round(($sizeMin + $sizeMax) / 3), null, null, 'plans/plans-05', '3rd–9th floor'],
                ['Full floor', $sizeMax, null, null, 'plans/plans-05', '10th–17th floor'],
            ];
        } else {
            [$bedsMin, $bedsMax] = [$row['beds'][0] ?? 2, $row['beds'][1] ?? $row['beds'][0] ?? 2];
            [$bathsMin, $bathsMax] = [$row['baths'][0] ?? $bedsMin, $row['baths'][1] ?? $row['baths'][0] ?? $bedsMax];
            $count = $sizeMin === $sizeMax ? 1 : ($bedsMin === $bedsMax ? 2 : 3);

            for ($index = 0; $index < $count; $index++) {
                $step = $count === 1 ? 0 : $index / ($count - 1);
                $beds = (int) round($bedsMin + ($bedsMax - $bedsMin) * $step);
                $plan = match (true) {
                    $beds <= 2 => 'plans/plans-01',
                    $beds === 3 => $index % 2 === 0 ? 'plans/plans-02' : 'plans/plans-03',
                    default => 'plans/plans-04',
                };
                $specs[] = ['Type '.chr(65 + $index), self::round($sizeMin + ($sizeMax - $sizeMin) * $step), $beds, (int) round($bathsMin + ($bathsMax - $bathsMin) * $step), $plan, '2nd–12th floor'];
            }

            if ($row['segment'] === 'mixed_use') {
                $specs[] = ['Shop', 650, null, null, 'plans/plans-06', 'Ground and 1st floor'];
            }
        }

        $units = [];

        foreach ($specs as $index => [$name, $size, $beds, $baths, $plan, $floors]) {
            $last = $index === count($specs) - 1 && count($specs) > 1;
            $availability = match ($row['booking_state']) {
                'sold_out' => 'sold_out',
                'limited_units' => $index === 0 ? 'sold_out' : 'limited',
                default => 'available',
            };
            // The largest type keeps its price hidden: shows the per-unit rule (AC-PROJ-02).
            $price = $row['price'] === null ? null : (int) (round($row['price'] * $size / $sizeMin / 100_000) * 100_000);

            $units[] = [
                'id' => $row['id'] * 100 + $index + 1,
                'name' => $name,
                'size_sft' => $size,
                'size_sft_max' => null,
                'bedrooms' => $beds,
                'bathrooms' => $baths,
                'balconies' => $beds === null ? null : max(1, $beds - 1),
                'facing' => self::FACINGS[($row['id'] + $index) % count(self::FACINGS)],
                'floor_range' => $floors,
                'floor_plan' => DemoMedia::image($plan),
                'plan_description' => self::planDescription($name, $size, $beds, $baths),
                'price_bdt' => $row['price_visible'] && ! $last ? $price : null,
                'availability' => $availability,
                'availability_label' => self::AVAILABILITY_LABELS[$availability],
            ];
        }

        return $units;
    }

    /**
     * A11Y-D11: the room list for screen-reader users (≤ 300 chars).
     */
    private static function planDescription(string $name, int $size, ?int $beds, ?int $baths): string
    {
        if ($beds === null) {
            return "{$name}, about ".Format::number($size).' sft: an open floor with a washroom and a store, served by the central lift core.';
        }

        $rooms = [$beds.' bedrooms', $baths.' bathrooms', 'living and dining', 'kitchen with utility', max(1, $beds - 1).' balconies'];

        return "{$name}, ".Format::number($size).' sft: '.implode(', ', $rooms).'. The master bedroom has an attached bath and faces the balcony.';
    }

    /**
     * Gallery in lightbox category order: exterior, interior, amenity, site,
     * night (CMP-GAL-01). The cover is always the first photo.
     *
     * @param  DemoProject  $row
     * @return list<GalleryItemData>
     */
    private static function gallery(array $row): array
    {
        $projects = DemoMedia::keys('projects');
        $other = $projects === [] ? $row['image'] : $projects[($row['id'] + 2) % count($projects)];

        $items = $row['segment'] === 'land'
            ? [
                ['exterior', $row['image'], "Aerial view of {$row['name']}"],
                ['site', 'locations/locations-04', 'Tree-lined streets in the neighbourhood'],
                ['site', 'locations/locations-02', 'The approach road'],
                ['site', 'progress/progress-04', 'Development work on the main road'],
                ['site', 'locations/locations-05', 'Homes already built nearby'],
            ]
            : [
                ['exterior', $row['image'], "Artist's impression of {$row['name']}"],
                ['exterior', $other, 'Street-side facade'],
                ...array_map(fn (string $key, int $index): array => ['interior', $key, ['Living room of a show apartment', 'Open-plan living and dining', 'Double-height living area', 'Lounge with timber panelling'][$index]], self::INTERIORS, array_keys(self::INTERIORS)),
                ['amenity', 'hero/hero-02', 'Garden pool for residents'],
                ['amenity', 'hero/hero-03', 'Rooftop pool and lounge deck'],
                ['site', 'progress/progress-01', 'Structure rising, floor slabs in place'],
                ['site', 'progress/progress-02', 'Column reinforcement on site'],
                ['night', 'hero/hero-01', 'The towers and pool at dusk'],
            ];

        return array_map(
            function (array $item, int $index): array {
                $image = DemoMedia::image($item[1]);

                return [
                    'id' => $index + 1,
                    'category' => $item[0],
                    'category_label' => self::GALLERY_LABELS[$item[0]],
                    'image' => $image === null ? null : ['caption' => $item[2]] + $image,
                ];
            },
            $items,
            array_keys($items),
        );
    }

    /**
     * Overview rich text (sanitised HTML in Part B, ≥ 120 words to publish).
     *
     * @param  DemoProject  $row
     * @param  array{name: string, city: string}  $area
     */
    private static function overview(array $row, array $area): string
    {
        $place = "{$area['name']}, {$area['city']}";

        return match ($row['segment']) {
            'land' => "<p>{$row['name']} is a gated plot development in {$place}. Every plot comes with a clear title, completed mutation and a registered deed, so you can start building as soon as the handover papers are signed.</p>"
                .'<p>Roads, drainage, street lighting and water lines are laid before the plots are handed over. The layout keeps wide internal roads, a central green and plots for a school and a mosque, and a boundary wall with a guarded gate closes the block.</p>'
                .'<p>Plots range from three to five katha and can be combined. Our team helps with RAJUK building approval and can recommend architects who have designed homes on neighbouring plots. Book a site visit to walk the plots with our engineer.</p>',
            'commercial' => "<p>{$row['name']} gives businesses a professional address in {$place}. Column-free floor plates, a high-speed lift core and full generator back-up make the floors easy to fit out for offices, clinics or showrooms.</p>"
                .'<p>The ground and first floors face the street for retail and banking, with their own entrance and signage. Upper floors can be bought as suites or as whole floors, and adjoining suites can be joined later without structural work.</p>'
                .'<p>Two basements of parking, a staffed reception lobby, CCTV in every common area and a building management office on site keep the tower running from the first day. A prayer room and a café on the ground floor serve tenants and visitors alike.</p>',
            default => "<p>{$row['name']} brings {$row['tagline']} to {$place}. The building is planned around light and air: every apartment has windows on at least two sides, deep balconies shade the living rooms, and the stair and lift core sits at the back so the street side stays open.</p>"
                .'<p>Floor plans keep the bedrooms away from the entrance and the kitchen close to the utility balcony, the way Bangladeshi families live. Master bedrooms have their own bathroom and a dressing area, and every apartment has a car park in the basement.</p>'
                .'<p>Residents share a landscaped rooftop, a community hall for family events, a gym and a prayer room. The structure is designed to the Bangladesh National Building Code for earthquake loads, and we publish construction progress every month until handover.</p>',
        };
    }

    /**
     * Feature highlights (repeater, ≤ 8): image, title, text.
     *
     * @param  DemoProject  $row
     * @return list<array{id: int, title: string, text: string, image: MediaImage|null}>
     */
    private static function highlights(array $row): array
    {
        $items = match ($row['segment']) {
            'land' => [
                ['locations/locations-04', 'A planned neighbourhood', 'Wide roads, a central green and plots reserved for a school and a mosque.'],
                ['progress/progress-04', 'Ready to build', 'Roads, drainage and water lines are complete before handover, with RAJUK approval support.'],
            ],
            'commercial' => [
                ['interiors/interiors-03', 'Column-free floors', 'Fit out offices, clinics or showrooms without working around columns.'],
                ['hero/hero-01', 'A landmark address', 'A tower that clients and staff find easily, with the brand on the facade.'],
            ],
            default => [
                ['interiors/interiors-01', 'Light from two sides', 'Every apartment has windows on at least two sides for daylight and cross-ventilation.'],
                ['hero/hero-03', 'A rooftop to share', 'A landscaped roof with a pool deck and seating for evenings with the family.'],
                ['interiors/interiors-04', 'Finished with care', 'Homogeneous floor tiles, timber doors and fittings from brands you know.'],
            ],
        };

        return array_map(
            fn (array $item, int $index): array => ['id' => $index + 1, 'title' => $item[1], 'text' => $item[2], 'image' => DemoMedia::image($item[0])],
            $items,
            array_keys($items),
        );
    }

    /**
     * DM-PROJ "At a glance" columns; the page labels and formats them.
     *
     * @param  DemoProject  $row
     * @param  array{name: string, city: string}  $area
     * @return array<string, mixed>
     */
    private static function facts(array $row, array $area): array
    {
        $id = $row['id'];
        $isLand = $row['segment'] === 'land';
        $storeys = $row['segment'] === 'commercial' ? 18 : 9 + $id % 6;
        $unitsPerFloor = $row['segment'] === 'commercial' ? null : 2 + $id % 3;

        return [
            'address' => sprintf('Plot %d, Road %d, %s, %s', 10 + $id * 3, 2 + $id % 9, $area['name'], $area['city']),
            'land_area_katha' => $isLand ? 80 + $id * 4 : 8 + ($id % 5) * 1.5,
            'storeys' => $isLand ? null : "2B + G + {$storeys}",
            'total_units' => $isLand ? 24 + $id : ($unitsPerFloor === null ? null : $unitsPerFloor * $storeys),
            'units_per_floor' => $unitsPerFloor,
            'lifts' => $isLand ? null : ($row['segment'] === 'commercial' ? 6 : 2),
            'car_parks' => $isLand ? null : ($unitsPerFloor ?? 6) * $storeys,
            'basements' => $isLand ? null : 2,
            'road_width_ft' => [30, 40, 60][$id % 3],
            'facing' => $isLand ? null : self::FACINGS[$id % count(self::FACINGS)],
            'orientation' => null,
            'launch_date' => substr($row['published_at'], 0, 7).'-01',
            'handover_date' => $row['handover'] === null ? null : $row['handover'].'-01',
            'rajuk_approval_no' => $isLand ? null : sprintf('RAJUK/DC/%s/%04d', substr($row['published_at'], 0, 4), 1200 + $id * 37),
        ];
    }

    /**
     * Custom facts (label/value repeater, ≤ 12); land plot sizes live here (edge case).
     *
     * @param  DemoProject  $row
     * @return list<array{label: string, value: string}>
     */
    private static function customFacts(array $row): array
    {
        return match ($row['segment']) {
            'land' => [
                ['label' => 'Plot sizes', 'value' => '3, 4 and 5 katha'],
                ['label' => 'Title', 'value' => 'Clear, mutation completed'],
            ],
            'commercial' => [['label' => 'Floor height', 'value' => '12 ft slab to slab']],
            default => [['label' => 'Ceiling height', 'value' => '10 ft']],
        };
    }

    private static function round(int|float $size): int
    {
        return (int) (round($size / 50) * 50);
    }
}
