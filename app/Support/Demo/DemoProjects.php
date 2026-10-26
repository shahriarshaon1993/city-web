<?php

namespace App\Support\Demo;

use InvalidArgumentException;

/**
 * Part A projects and areas (DEC-29): DM-PROJ / DM-LOC rows reduced to what
 * the cards, the listing (DemoProjectSearch) and the menus need. Placeholder
 * names and figures until the client's list arrives; prices of
 * `price_visible: false` rows never leave the server (AC-PROJ-01). IDs are
 * the row position (1-based) and must stay stable: the shortlist stores them.
 * Deleted with DemoContent in 16.4.
 *
 * @phpstan-type DemoProjectRow array{slug: string, name: string, tagline: string, segment: string, lifecycle: string, booking_state: string, featured: bool, area: string, image: string, size: array{0: int|null, 1: int|null}, beds: array{0: int|null, 1: int|null}, baths: array{0: int|null, 1: int|null}, katha: int|float|null, price: int|null, price_visible: bool, published_at: string, handover: string|null}
 * @phpstan-type DemoProject array{id: int, featured_order: int|null, slug: string, name: string, tagline: string, segment: string, lifecycle: string, booking_state: string, featured: bool, area: string, image: string, size: array{0: int|null, 1: int|null}, beds: array{0: int|null, 1: int|null}, baths: array{0: int|null, 1: int|null}, katha: int|float|null, price: int|null, price_visible: bool, published_at: string, handover: string|null}
 * @phpstan-type DemoArea array{slug: string, name: string, city: string, count: int}
 * @phpstan-type ProjectCardData array{id: int, slug: string, url: string, name: string, segment: string, segment_label: string, lifecycle: string, lifecycle_label: string, booking_state: string, booking_label: string|null, is_featured: bool, location: array{area: string, city: string}, cover: MediaImage|null, size_min_sft: int|null, size_max_sft: int|null, beds_min: int|null, beds_max: int|null, baths_min: int|null, baths_max: int|null, land_katha: int|float|null, price_from_bdt: int|null, price_note: string|null}
 *
 * @phpstan-import-type MediaImage from DemoMedia
 */
final class DemoProjects
{
    public const array SEGMENT_LABELS = ['residential' => 'Residential', 'commercial' => 'Commercial', 'mixed_use' => 'Mixed-use', 'land' => 'Land'];

    public const array LIFECYCLE_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'ready' => 'Ready', 'handed_over' => 'Handed over'];

    public const array BOOKING_LABELS = ['booking_open' => 'Booking open', 'limited_units' => 'Limited units', 'sold_out' => 'Sold out'];

    /**
     * DM-LOC areas: slug => [name, city], Dhaka first (HERO-040 city groups).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array AREAS = [
        'bashundhara-ra' => ['Bashundhara R/A', 'Dhaka'],
        'gulshan' => ['Gulshan', 'Dhaka'],
        'banani' => ['Banani', 'Dhaka'],
        'baridhara' => ['Baridhara', 'Dhaka'],
        'dhanmondi' => ['Dhanmondi', 'Dhaka'],
        'uttara' => ['Uttara', 'Dhaka'],
        'mirpur' => ['Mirpur', 'Dhaka'],
        'mohammadpur' => ['Mohammadpur', 'Dhaka'],
        'khulshi' => ['Khulshi', 'Chattogram'],
        'panchlaish' => ['Panchlaish', 'Chattogram'],
    ];

    /**
     * Rows 1–8 are the original home/menu projects (featured order = row
     * order); 9–26 were added in 4.1 so the listing has three pages.
     * `handover` is month precision (`Y-m`).
     *
     * @var list<DemoProjectRow>
     */
    private const array ROWS = [
        ['slug' => 'gemcon-lakeview', 'name' => 'Gemcon Lakeview', 'tagline' => 'Lake-facing family apartments with a rooftop garden', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'bashundhara-ra', 'image' => 'projects/projects-01', 'size' => [1650, 2100], 'beds' => [3, 4], 'baths' => [3, 4], 'katha' => null, 'price' => 14_500_000, 'price_visible' => true, 'published_at' => '2025-08-12', 'handover' => '2027-12'],
        ['slug' => 'gemcon-orchid-heights', 'name' => 'Gemcon Orchid Heights', 'tagline' => 'Twenty-four four-bedroom residences by the lake', 'segment' => 'residential', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'gulshan', 'image' => 'projects/projects-02', 'size' => [2400, 3200], 'beds' => [4, 4], 'baths' => [4, 5], 'katha' => null, 'price' => 42_000_000, 'price_visible' => false, 'published_at' => '2026-09-05', 'handover' => '2029-12'],
        ['slug' => 'gemcon-trade-centre', 'name' => 'Gemcon Trade Centre', 'tagline' => 'An 18-storey office and retail tower on Kemal Ataturk Avenue', 'segment' => 'commercial', 'lifecycle' => 'ongoing', 'booking_state' => 'limited_units', 'featured' => true, 'area' => 'banani', 'image' => 'projects/projects-03', 'size' => [850, 5200], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => 9_500_000, 'price_visible' => true, 'published_at' => '2025-03-15', 'handover' => '2027-06'],
        ['slug' => 'gemcon-greenfield', 'name' => 'Gemcon Greenfield', 'tagline' => 'Ready apartments beside the Sector 7 park', 'segment' => 'residential', 'lifecycle' => 'ready', 'booking_state' => 'limited_units', 'featured' => true, 'area' => 'uttara', 'image' => 'projects/projects-04', 'size' => [1250, 1450], 'beds' => [3, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 8_500_000, 'price_visible' => true, 'published_at' => '2024-01-20', 'handover' => '2025-06'],
        ['slug' => 'gemcon-riverside-plots', 'name' => 'Gemcon Riverside Plots', 'tagline' => 'Five-katha plots with clear titles near the Turag', 'segment' => 'land', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'mirpur', 'image' => 'projects/projects-05', 'size' => [null, null], 'beds' => [null, null], 'baths' => [null, null], 'katha' => 5, 'price' => 12_500_000, 'price_visible' => true, 'published_at' => '2025-06-30', 'handover' => null],
        ['slug' => 'gemcon-crescent', 'name' => 'Gemcon Crescent', 'tagline' => 'Homes above a neighbourhood high street', 'segment' => 'mixed_use', 'lifecycle' => 'upcoming', 'booking_state' => 'none', 'featured' => false, 'area' => 'dhanmondi', 'image' => 'projects/projects-06', 'size' => [1100, 2600], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => null, 'price_visible' => false, 'published_at' => '2026-05-18', 'handover' => '2029-06'],
        ['slug' => 'gemcon-palm-court', 'name' => 'Gemcon Palm Court', 'tagline' => 'Thirty-two duplex-height apartments around a palm court', 'segment' => 'residential', 'lifecycle' => 'handed_over', 'booking_state' => 'sold_out', 'featured' => false, 'area' => 'baridhara', 'image' => 'projects/projects-07', 'size' => [2800, 2800], 'beds' => [4, 4], 'baths' => [5, 5], 'katha' => null, 'price' => null, 'price_visible' => false, 'published_at' => '2022-09-01', 'handover' => '2026-06'],
        ['slug' => 'gemcon-maple-residency', 'name' => 'Gemcon Maple Residency', 'tagline' => 'Compact two- and three-bedroom homes near Asad Avenue', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => true, 'area' => 'mohammadpur', 'image' => 'projects/projects-08', 'size' => [1050, 1350], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 6_800_000, 'price_visible' => true, 'published_at' => '2026-02-02', 'handover' => '2028-06'],
        ['slug' => 'gemcon-azure-residence', 'name' => 'Gemcon Azure Residence', 'tagline' => 'Three- and four-bedroom apartments on a quiet Sector 13 lane', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'uttara', 'image' => 'projects/projects-02', 'size' => [1450, 2100], 'beds' => [3, 4], 'baths' => [3, 4], 'katha' => null, 'price' => 9_800_000, 'price_visible' => true, 'published_at' => '2026-06-14', 'handover' => '2028-06'],
        ['slug' => 'gemcon-banani-square', 'name' => 'Gemcon Banani Square', 'tagline' => 'Flexible office floors with a public plaza', 'segment' => 'commercial', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'banani', 'image' => 'projects/projects-03', 'size' => [1200, 8000], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => 18_000_000, 'price_visible' => true, 'published_at' => '2026-09-28', 'handover' => '2029-12'],
        ['slug' => 'gemcon-cedar-court', 'name' => 'Gemcon Cedar Court', 'tagline' => 'Ready homes two minutes from Dhanmondi Lake', 'segment' => 'residential', 'lifecycle' => 'ready', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'dhanmondi', 'image' => 'projects/projects-04', 'size' => [1900, 2300], 'beds' => [3, 4], 'baths' => [3, 4], 'katha' => null, 'price' => 21_500_000, 'price_visible' => true, 'published_at' => '2025-11-02', 'handover' => '2026-03'],
        ['slug' => 'gemcon-hillview', 'name' => 'Gemcon Hillview', 'tagline' => 'Hillside apartments with views over Chattogram', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'khulshi', 'image' => 'projects/projects-01', 'size' => [1350, 1700], 'beds' => [3, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 7_900_000, 'price_visible' => true, 'published_at' => '2026-04-20', 'handover' => '2028-03'],
        ['slug' => 'gemcon-north-plots', 'name' => 'Gemcon North Plots', 'tagline' => 'Three-katha residential plots in a gated block', 'segment' => 'land', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'uttara', 'image' => 'projects/projects-05', 'size' => [null, null], 'beds' => [null, null], 'baths' => [null, null], 'katha' => 3, 'price' => 9_000_000, 'price_visible' => true, 'published_at' => '2026-08-30', 'handover' => null],
        ['slug' => 'gemcon-gulshan-avenue', 'name' => 'Gemcon Gulshan Avenue', 'tagline' => 'Corner office tower on Gulshan Avenue', 'segment' => 'commercial', 'lifecycle' => 'ongoing', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'gulshan', 'image' => 'projects/projects-03', 'size' => [900, 4200], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => 26_000_000, 'price_visible' => false, 'published_at' => '2025-12-10', 'handover' => '2027-09'],
        ['slug' => 'gemcon-jasmine-heights', 'name' => 'Gemcon Jasmine Heights', 'tagline' => 'Garden-terrace apartments in Block D', 'segment' => 'residential', 'lifecycle' => 'upcoming', 'booking_state' => 'none', 'featured' => false, 'area' => 'bashundhara-ra', 'image' => 'projects/projects-06', 'size' => [1600, 2000], 'beds' => [3, 4], 'baths' => [3, 4], 'katha' => null, 'price' => 13_200_000, 'price_visible' => true, 'published_at' => '2026-10-12', 'handover' => '2029-06'],
        ['slug' => 'gemcon-silverline', 'name' => 'Gemcon Silverline', 'tagline' => 'Forty family homes, handed over on time', 'segment' => 'residential', 'lifecycle' => 'handed_over', 'booking_state' => 'sold_out', 'featured' => false, 'area' => 'mirpur', 'image' => 'projects/projects-07', 'size' => [1100, 1300], 'beds' => [2, 3], 'baths' => [2, 2], 'katha' => null, 'price' => null, 'price_visible' => false, 'published_at' => '2023-02-01', 'handover' => '2025-08'],
        ['slug' => 'gemcon-meridian', 'name' => 'Gemcon Meridian', 'tagline' => 'Apartments over a ground-floor market hall', 'segment' => 'mixed_use', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'bashundhara-ra', 'image' => 'projects/projects-08', 'size' => [950, 2400], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 11_000_000, 'price_visible' => true, 'published_at' => '2026-02-15', 'handover' => '2028-12'],
        ['slug' => 'gemcon-lotus-garden', 'name' => 'Gemcon Lotus Garden', 'tagline' => 'Ready three-bedroom homes around a courtyard', 'segment' => 'residential', 'lifecycle' => 'ready', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'mohammadpur', 'image' => 'projects/projects-02', 'size' => [1250, 1500], 'beds' => [3, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 8_200_000, 'price_visible' => true, 'published_at' => '2025-09-18', 'handover' => '2026-06'],
        ['slug' => 'gemcon-skyline-tower', 'name' => 'Gemcon Skyline Tower', 'tagline' => 'Ready office suites on CDA Avenue', 'segment' => 'commercial', 'lifecycle' => 'ready', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'panchlaish', 'image' => 'projects/projects-03', 'size' => [800, 3600], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => 7_500_000, 'price_visible' => true, 'published_at' => '2025-10-05', 'handover' => '2026-05'],
        ['slug' => 'gemcon-baridhara-residences', 'name' => 'Gemcon Baridhara Residences', 'tagline' => 'Five-bedroom residences on the diplomatic zone lake', 'segment' => 'residential', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'baridhara', 'image' => 'projects/projects-01', 'size' => [3000, 3800], 'beds' => [4, 5], 'baths' => [5, 6], 'katha' => null, 'price' => 65_000_000, 'price_visible' => false, 'published_at' => '2026-07-22', 'handover' => '2029-09'],
        ['slug' => 'gemcon-dhanmondi-lakeside', 'name' => 'Gemcon Dhanmondi Lakeside', 'tagline' => 'Sixteen lakeside apartments, fully sold', 'segment' => 'residential', 'lifecycle' => 'handed_over', 'booking_state' => 'sold_out', 'featured' => false, 'area' => 'dhanmondi', 'image' => 'projects/projects-04', 'size' => [2100, 2500], 'beds' => [4, 4], 'baths' => [4, 4], 'katha' => null, 'price' => null, 'price_visible' => false, 'published_at' => '2022-05-10', 'handover' => '2024-12'],
        ['slug' => 'gemcon-greenvalley-plots', 'name' => 'Gemcon Greenvalley Plots', 'tagline' => 'Four-katha plots facing a 60 ft road', 'segment' => 'land', 'lifecycle' => 'ongoing', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'bashundhara-ra', 'image' => 'projects/projects-05', 'size' => [null, null], 'beds' => [null, null], 'baths' => [null, null], 'katha' => 4, 'price' => 16_000_000, 'price_visible' => true, 'published_at' => '2026-03-08', 'handover' => null],
        ['slug' => 'gemcon-northgate', 'name' => 'Gemcon Northgate', 'tagline' => 'Retail and office podium beside the metro station', 'segment' => 'commercial', 'lifecycle' => 'upcoming', 'booking_state' => 'none', 'featured' => false, 'area' => 'uttara', 'image' => 'projects/projects-08', 'size' => [1000, 6000], 'beds' => [null, null], 'baths' => [null, null], 'katha' => null, 'price' => null, 'price_visible' => false, 'published_at' => '2026-10-20', 'handover' => '2030-06'],
        ['slug' => 'gemcon-orchard-view', 'name' => 'Gemcon Orchard View', 'tagline' => 'Four-bedroom homes overlooking Gulshan Lake Park', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'gulshan', 'image' => 'projects/projects-06', 'size' => [2600, 3100], 'beds' => [4, 4], 'baths' => [4, 5], 'katha' => null, 'price' => 34_000_000, 'price_visible' => true, 'published_at' => '2026-01-25', 'handover' => '2028-09'],
        ['slug' => 'gemcon-harbour-court', 'name' => 'Gemcon Harbour Court', 'tagline' => 'Homes and studios above a shopping arcade', 'segment' => 'mixed_use', 'lifecycle' => 'upcoming', 'booking_state' => 'booking_open', 'featured' => false, 'area' => 'khulshi', 'image' => 'projects/projects-07', 'size' => [1000, 2200], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 8_900_000, 'price_visible' => true, 'published_at' => '2026-09-10', 'handover' => '2029-03'],
        ['slug' => 'gemcon-rosewood', 'name' => 'Gemcon Rosewood', 'tagline' => 'Two- and three-bedroom homes near the school zone', 'segment' => 'residential', 'lifecycle' => 'ongoing', 'booking_state' => 'limited_units', 'featured' => false, 'area' => 'bashundhara-ra', 'image' => 'projects/projects-02', 'size' => [1200, 1450], 'beds' => [2, 3], 'baths' => [2, 3], 'katha' => null, 'price' => 7_200_000, 'price_visible' => true, 'published_at' => '2025-12-28', 'handover' => '2027-12'],
    ];

    /**
     * Rows with their ID (row position, 1-based) and featured order.
     *
     * @return list<DemoProject>
     */
    public static function rows(): array
    {
        $rows = [];
        $featuredOrder = 0;

        foreach (self::ROWS as $index => $row) {
            $rows[] = [...$row, 'id' => $index + 1, 'featured_order' => $row['featured'] ? ++$featuredOrder : null];
        }

        return $rows;
    }

    /**
     * A row by slug, with its ID.
     *
     * @return DemoProject
     */
    public static function row(string $slug): array
    {
        foreach (self::rows() as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }

        throw new InvalidArgumentException("Unknown demo project {$slug}.");
    }

    /**
     * Every area with its published project count, in AREAS order.
     *
     * @return list<DemoArea>
     */
    public static function areas(): array
    {
        $counts = array_count_values(array_column(self::ROWS, 'area'));

        return array_map(
            fn (string $slug, array $area): array => ['slug' => $slug, 'name' => $area[0], 'city' => $area[1], 'count' => $counts[$slug] ?? 0],
            array_keys(self::AREAS),
            array_values(self::AREAS),
        );
    }

    /**
     * The top areas by project count (CMS-NAV-003 `auto:locations`), ties in AREAS order.
     *
     * @return list<DemoArea>
     */
    public static function topAreas(int $limit = 8): array
    {
        $areas = self::areas();
        usort($areas, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($areas, 0, $limit);
    }

    /**
     * @return array{name: string, city: string}
     */
    public static function area(string $slug): array
    {
        $area = self::AREAS[$slug] ?? throw new InvalidArgumentException("Unknown demo area {$slug}.");

        return ['name' => $area[0], 'city' => $area[1]];
    }

    /**
     * ProjectCardResource shape (ProjectCard in types/site.ts, CMP-CARD-01).
     *
     * @param  DemoProject  $row
     * @return ProjectCardData
     */
    public static function card(array $row): array
    {
        return [
            'id' => $row['id'],
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
            'location' => ['area' => self::area($row['area'])['name'], 'city' => self::area($row['area'])['city']],
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
}
