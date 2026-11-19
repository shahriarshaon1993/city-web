<?php

namespace Database\Seeders\Data;

use App\Support\Catalog\CatalogCopy;
use App\Support\Format;
use App\Support\Settings\Settings;
use Illuminate\Support\Carbon;

/**
 * Part A data for the PG-04 sections of 4.3 (DEC-29): video and virtual
 * tour, construction progress (DM-PROG), map coordinates, nearby places
 * (DM-NEAR), design team, documents (DM-PDOC), project FAQ, similar
 * projects (AC-PROJ-07) and the enquiry panel. Derived from a DemoProjects
 * row like DemoProjectDetail; placeholder wording and fictional firms. Gated
 * documents carry no URL (AC-PROJ-05: a signed URL follows the lead).
 * 13.3 replaces this with ProjectDetailResource relations; deleted in 16.4.
 *
 * @phpstan-import-type DemoProject from DemoProjects
 * @phpstan-import-type ProjectCardData from DemoProjects
 * @phpstan-import-type MediaImage from SeedMedia
 */
final class DemoProjectSections
{
    /** Owner, 4.3: Blender's "Big Buck Bunny" until Gemcon's own films arrive. */
    public const string DEMO_YOUTUBE_ID = 'aqz-KE-bpKQ';

    public const int DEMO_YOUTUBE_SECONDS = 634;

    /**
     * Latest DM-PROG update of the three ongoing demo projects; placeholder figures until the client's
     * reports arrive.
     *
     * @var list<array{project: string, percent: int, stage: string, reported_on: string, note: string, photo: string}>
     */
    private const array PROGRESS = [
        ['project' => 'gemcon-trade-centre', 'percent' => 81, 'stage' => 'finishing', 'reported_on' => '2026-10-01', 'note' => 'Curtain wall glazing is complete up to level 14; lift installation has started in the east core.', 'photo' => 'progress/progress-01'],
        ['project' => 'gemcon-lakeview', 'percent' => 64, 'stage' => 'structure', 'reported_on' => '2026-09-01', 'note' => 'Roof slab of Tower B is cast. Brickwork and electrical conduits are under way on levels 1–9.', 'photo' => 'progress/progress-02'],
        ['project' => 'gemcon-maple-residency', 'percent' => 18, 'stage' => 'foundation', 'reported_on' => '2026-08-01', 'note' => 'Piling is finished and the raft foundation is being poured in three sections.', 'photo' => 'progress/progress-03'],
    ];

    /**
     * @return array{project: string, percent: int, stage: string, reported_on: string, note: string, photo: string}|null
     */
    private static function latestProgress(string $slug): ?array
    {
        return array_find(self::PROGRESS, fn (array $update): bool => $update['project'] === $slug);
    }

    /** Placeholder tour on an allowed host (FR-PROJ media: my.matterport.com, kuula.co, www.youtube.com). */
    private const string DEMO_TOUR_URL = 'https://my.matterport.com/show/?m=SxQL3iGyoDo';

    public const array NEARBY_LABELS = ['school' => 'School', 'hospital' => 'Hospital', 'mall' => 'Shopping', 'mosque' => 'Mosque', 'transport' => 'Transport', 'park' => 'Park', 'office' => 'Office', 'airport' => 'Airport'];

    public const array MODE_LABELS = ['drive' => 'drive', 'walk' => 'walk'];

    public const array DOCUMENT_LABELS = ['brochure' => 'Brochure', 'price_list' => 'Price list', 'specification' => 'Specification', 'other' => 'Document'];

    /** DM-NEAR rows per area: name, category, minutes, mode (≤ 10 per project). */
    private const array AREA_NEARBY = [
        'bashundhara-ra' => [['North South University', 'school', 6, 'drive'], ['Evercare Hospital Dhaka', 'hospital', 8, 'drive'], ['Jamuna Future Park', 'mall', 10, 'drive'], ['Bashundhara Central Mosque', 'mosque', 5, 'walk'], ['Hazrat Shahjalal International Airport', 'airport', 20, 'drive']],
        'gulshan' => [['Gulshan Lake Park', 'park', 6, 'walk'], ['United Hospital', 'hospital', 7, 'drive'], ['Gulshan 2 Circle', 'office', 5, 'drive'], ['Gulshan Central Mosque', 'mosque', 8, 'walk'], ['Hazrat Shahjalal International Airport', 'airport', 25, 'drive']],
        'banani' => [['Banani Lake', 'park', 5, 'walk'], ['Banani Road 11 shops', 'mall', 8, 'walk'], ['Kemal Ataturk Avenue offices', 'office', 4, 'walk'], ['Banani Station', 'transport', 10, 'drive'], ['Hazrat Shahjalal International Airport', 'airport', 20, 'drive']],
        'baridhara' => [['Baridhara Park', 'park', 5, 'walk'], ['International school campus', 'school', 7, 'drive'], ['United Hospital', 'hospital', 10, 'drive'], ['Gulshan 2 Circle', 'office', 8, 'drive'], ['Hazrat Shahjalal International Airport', 'airport', 22, 'drive']],
        'dhanmondi' => [['Dhanmondi Lake', 'park', 3, 'walk'], ['Labaid Specialized Hospital', 'hospital', 8, 'drive'], ['Dhanmondi Road 27 shops', 'mall', 6, 'drive'], ['Taqwa Mosque', 'mosque', 7, 'walk'], ['Dhaka University', 'school', 15, 'drive']],
        'uttara' => [['Uttara North metro station', 'transport', 6, 'drive'], ['Sector 7 park', 'park', 4, 'walk'], ['Kuwait Bangladesh Friendship Hospital', 'hospital', 9, 'drive'], ['Rajlakshmi Complex', 'mall', 8, 'drive'], ['Hazrat Shahjalal International Airport', 'airport', 12, 'drive']],
        'mirpur' => [['Mirpur 10 metro station', 'transport', 8, 'drive'], ['National Botanical Garden', 'park', 12, 'drive'], ['Mirpur Shopping Complex', 'mall', 7, 'drive'], ['Local primary and high school', 'school', 6, 'walk'], ['Turag riverside', 'park', 10, 'drive']],
        'mohammadpur' => [['Asad Avenue', 'transport', 4, 'walk'], ['Mohammadpur Town Hall market', 'mall', 6, 'walk'], ['Bangladesh Medical College Hospital', 'hospital', 10, 'drive'], ['Mohammadpur Shia Mosque', 'mosque', 8, 'walk'], ['Dhanmondi Lake', 'park', 12, 'drive']],
        'khulshi' => [['Khulshi hill walk', 'park', 5, 'walk'], ['Chittagong Medical College Hospital', 'hospital', 15, 'drive'], ['GEC Circle shops', 'mall', 10, 'drive'], ['Chittagong Grammar School', 'school', 8, 'drive'], ['Shah Amanat International Airport', 'airport', 35, 'drive']],
        'panchlaish' => [['CDA Avenue offices', 'office', 4, 'walk'], ['Chittagong Medical College Hospital', 'hospital', 6, 'drive'], ['Sanmar Ocean City', 'mall', 8, 'drive'], ['Panchlaish central mosque', 'mosque', 5, 'walk'], ['Shah Amanat International Airport', 'airport', 30, 'drive']],
    ];

    /** Fictional firms: architect, structural engineer, contractor. */
    private const array DESIGN_FIRMS = [
        ['Studio Meghna Architects', 'Kolpo Structural Consultants', 'Gemcon Construction'],
        ['Atelier Shapla', 'Sthapotto Engineering', 'Gemcon Construction'],
        ['Nodi Design Partnership', 'Kolpo Structural Consultants', 'Gemcon Construction'],
    ];

    /** Notes for derived progress updates, per stage. */
    private const array PROGRESS_NOTES = [
        'foundation' => ['Excavation for the basements is finished and the retaining walls are going up.', 'Piling is complete; the pile caps are being cast in sections.'],
        'structure' => ['The basement and ground-floor slabs are cast; columns for level 1 are being reinforced.', 'Slabs are complete up to level 6 and the stair core is rising ahead of them.'],
        'finishing' => ['Brickwork is complete; plastering and window frames are under way on every floor.', 'Lifts are being installed and floor tiling has started on the lower floors.'],
        'complete' => ['Construction is complete and the building has its occupancy certificate.'],
    ];

    /**
     * CMP-VIDEO-01 data: an uploaded film with captions (owner drops
     * `video/project.mp4`, Lakeview only), else the YouTube placeholder on
     * two of three building projects. Land has no video.
     *
     * @param  DemoProject  $row
     * @return array<string, mixed>|null
     */
    public static function video(array $row): ?array
    {
        if ($row['segment'] === 'land') {
            return null;
        }

        $title = "{$row['name']} project film";
        $poster = SeedMedia::image('hero/hero-0'.(1 + $row['id'] % 3));

        if ($row['slug'] === 'gemcon-lakeview') {
            $file = SeedMedia::video('video/project');

            return $file === null ? null : [
                'type' => 'upload',
                'title' => $title,
                'poster' => $poster,
                'duration_seconds' => null,
                'mp4' => $file['mp4'],
                'webm' => $file['webm'],
                'captions' => SeedMedia::captions('video/project'),
            ];
        }

        return $row['id'] % 3 === 0 ? null : [
            'type' => 'youtube',
            'title' => $title,
            'poster' => $poster,
            'duration_seconds' => self::DEMO_YOUTUBE_SECONDS,
            'youtube_id' => self::DEMO_YOUTUBE_ID,
        ];
    }

    /**
     * @param  DemoProject  $row
     */
    public static function virtualTour(array $row): ?string
    {
        return $row['segment'] === 'residential' && $row['id'] % 4 === 1 ? self::DEMO_TOUR_URL : null;
    }

    /**
     * DM-PROG updates, newest first: none before construction starts,
     * ending at 100 % for ready and handed-over projects. The newest update
     * matches the home progress block where the project appears there.
     *
     * @param  DemoProject  $row
     * @return list<array{id: int, reported_on: string, percent: int, stage: string, stage_label: string, note: string|null, photos: list<MediaImage>}>
     */
    public static function progress(array $row): array
    {
        if ($row['lifecycle'] === 'upcoming') {
            return [];
        }

        $complete = in_array($row['lifecycle'], ['ready', 'handed_over'], true);
        $home = self::latestProgress($row['slug']);
        $latest = match (true) {
            $complete => ['percent' => 100, 'stage' => 'complete', 'reported_on' => ($row['handover'] ?? '2026-10').'-01', 'note' => self::PROGRESS_NOTES['complete'][0]],
            $home !== null => $home,
            default => ['percent' => 12 + ($row['id'] * 17) % 75, 'stage' => null, 'reported_on' => Carbon::parse('2026-10-01')->subMonths($row['id'] % 3)->toDateString(), 'note' => null],
        };

        $count = $complete ? 3 : 4;
        $photos = SeedMedia::keys('progress');
        $stages = array_column(CatalogCopy::progressStages(), 'label', 'value');
        $updates = [];

        for ($index = 0; $index < $count; $index++) {
            $percent = $index === 0 ? $latest['percent'] : max(2, (int) round($latest['percent'] * ($count - $index) / ($count + 1)));
            $stage = $index === 0 && $latest['stage'] !== null ? $latest['stage'] : self::stageFor($percent);
            $date = Carbon::parse($latest['reported_on'])->subMonths($index * 2);
            $notes = self::PROGRESS_NOTES[$stage];
            $month = Format::month($date);

            $updates[] = [
                'id' => $row['id'] * 100 + $index + 1,
                'reported_on' => $date->toDateString(),
                'percent' => $percent,
                'stage' => $stage,
                'stage_label' => $stages[$stage],
                'note' => $index === 0 && $latest['note'] !== null ? $latest['note'] : $notes[($row['id'] + $index) % count($notes)],
                'photos' => array_values(array_filter(array_map(
                    function (int $offset) use ($photos, $row, $index, $month): ?array {
                        $image = $photos === [] ? null : SeedMedia::image($photos[($row['id'] + $index + $offset) % count($photos)]);

                        return $image === null ? null : ['caption' => "Site progress, {$month}"] + $image;
                    },
                    range(0, $index === 0 ? 2 : 1),
                ))),
            ];
        }

        return $updates;
    }

    /**
     * @param  DemoProject  $row
     * @return array{lat: float, lng: float}
     */
    public static function coordinates(array $row): array
    {
        // Each project sits a little off its area's centre.
        ['lat' => $lat, 'lng' => $lng] = DemoLocations::centre($row['area']);
        $shift = fn (int $seed): float => (($row['id'] * $seed) % 60 - 30) / 10_000;

        return ['lat' => round($lat + $shift(37), 6), 'lng' => round($lng + $shift(53), 6)];
    }

    /**
     * @param  DemoProject  $row
     * @return list<array{id: int, name: string, category: string, category_label: string, minutes: int, mode: string, mode_label: string}>
     */
    public static function nearby(array $row): array
    {
        return array_map(
            fn (array $place, int $index): array => [
                'id' => $index + 1,
                'name' => $place[0],
                'category' => $place[1],
                'category_label' => self::NEARBY_LABELS[$place[1]],
                // A few minutes more or less per project, so pages differ.
                'minutes' => max(2, $place[2] + ($row['id'] + $index) % 3 - 1),
                'mode' => $place[3],
                'mode_label' => self::MODE_LABELS[$place[3]],
            ],
            self::AREA_NEARBY[$row['area']],
            array_keys(self::AREA_NEARBY[$row['area']]),
        );
    }

    /**
     * DM-PROJ architect, structural_engineer, contractor (empty for land).
     *
     * @param  DemoProject  $row
     * @return list<array{role: string, role_label: string, name: string}>
     */
    public static function designTeam(array $row): array
    {
        if ($row['segment'] === 'land') {
            return [];
        }

        $firms = self::DESIGN_FIRMS[$row['id'] % count(self::DESIGN_FIRMS)];

        return [
            ['role' => 'architect', 'role_label' => 'Architect', 'name' => $firms[0]],
            ['role' => 'structural_engineer', 'role_label' => 'Structural engineer', 'name' => $firms[1]],
            ['role' => 'contractor', 'role_label' => 'Contractor', 'name' => $firms[2]],
        ];
    }

    /**
     * DM-PDOC rows: the gated brochure (DM-PROJ `brochure_gated`), a gated
     * price list when prices are shown, and a public specification (land:
     * plot layout). Gated rows never carry a URL.
     *
     * @param  DemoProject  $row
     * @return list<array{id: int, title: string, kind: string, kind_label: string, is_gated: bool, url: string|null, bytes: int|null, format: string}>
     */
    public static function documents(array $row): array
    {
        $isLand = $row['segment'] === 'land';
        $items = [
            ['brochure', 'brochure', "{$row['name']} brochure", true],
            ...($row['price_visible'] ? [['price_list', 'price-list', 'Price list and payment schedule', true]] : []),
            $isLand
                ? ['other', 'plot-layout', 'Plot layout plan', false]
                : ['specification', 'specification', 'Specification and finishes', false],
        ];

        return array_map(
            function (array $item, int $index) use ($row): array {
                [$kind, $file, $title, $gated] = $item;
                $document = SeedMedia::document('documents/'.$file);

                return [
                    'id' => $row['id'] * 100 + $index + 1,
                    'title' => $title,
                    'kind' => $kind,
                    'kind_label' => self::DOCUMENT_LABELS[$kind],
                    'is_gated' => $gated,
                    'url' => $gated ? null : $document['url'] ?? null,
                    'bytes' => $document['bytes'] ?? null,
                    'format' => 'PDF',
                ];
            },
            $items,
            array_keys($items),
        );
    }

    /**
     * AC-PROJ-07: no manual picks in the demo, so up to 3 projects of the
     * same segment and city, newest first, excluding the project itself.
     *
     * @param  DemoProject  $row
     * @return list<ProjectCardData>
     */
    public static function similar(array $row): array
    {
        $city = DemoProjects::area($row['area'])['city'];
        $matches = array_filter(
            DemoProjects::rows(),
            fn (array $other): bool => $other['id'] !== $row['id']
                && $other['segment'] === $row['segment']
                && DemoProjects::area($other['area'])['city'] === $city,
        );

        usort($matches, fn (array $a, array $b): int => strcmp($b['published_at'], $a['published_at']));

        return array_map(DemoProjects::card(...), array_slice($matches, 0, 3));
    }

    /**
     * CMP-ENQ-01: heading (handed-over edge case), the WhatsApp number
     * (project override, else Settings → Contact) and its prefilled text
     * (FR-LEAD-013).
     *
     * @param  DemoProject  $row
     * @return array{heading: string, whatsapp: string|null, whatsapp_text: string}
     */
    public static function enquiry(array $row): array
    {
        return [
            'heading' => $row['lifecycle'] === 'handed_over'
                ? 'Interested in a resale or a similar project?'
                : "Interested in {$row['name']}?",
            'whatsapp' => Settings::group('contact')['whatsapp_number'] ?? null,
            'whatsapp_text' => "Hi, I'm interested in {$row['name']}. ".url('/projects/'.$row['slug']),
        ];
    }

    private static function stageFor(int $percent): string
    {
        return match (true) {
            $percent >= 100 => 'complete',
            $percent >= 70 => 'finishing',
            $percent >= 25 => 'structure',
            default => 'foundation',
        };
    }
}
