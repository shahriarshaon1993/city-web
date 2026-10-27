<?php

namespace App\Support\Demo;

/**
 * Part A area pages (DEC-29): the DM-LOC fields DemoProjects leaves out —
 * hero image, intro, facts (≤ 8), centre + zoom, FAQ (≤ 8), nearby areas
 * (≤ 6) and the optional area × segment intros (FR-LOC-002/004). Six areas
 * have an intro of ≥ 150 words; the rest stay short so FR-LOC-003's noindex
 * rule shows on demo data. Placeholder copy; 13.1 moves it to `locations`.
 * Deleted with DemoContent in 16.4.
 *
 * @phpstan-type DemoAreaDetail array{image: string, intro: list<string>, facts: list<array{0: string, 1: string}>, faq: list<array{0: string, 1: string}>, nearby: list<string>, segments: array<string, string>}
 */
final class DemoLocations
{
    /**
     * Area centre (lat, lng) and map zoom (11–16).
     *
     * @var array<string, array{0: float, 1: float, 2: int}>
     */
    private const array CENTRES = [
        'bashundhara-ra' => [23.8193, 90.4526, 15],
        'gulshan' => [23.7925, 90.4078, 15],
        'banani' => [23.7940, 90.4043, 15],
        'baridhara' => [23.7999, 90.4211, 15],
        'dhanmondi' => [23.7461, 90.3742, 15],
        'uttara' => [23.8759, 90.3795, 14],
        'mirpur' => [23.8223, 90.3654, 14],
        'mohammadpur' => [23.7662, 90.3589, 15],
        'khulshi' => [22.3601, 91.8102, 15],
        'panchlaish' => [22.3637, 91.8317, 15],
        'purbachal' => [23.8420, 90.5040, 13],
    ];

    /**
     * @var array<string, DemoAreaDetail>
     */
    private const array DETAILS = [
        'bashundhara-ra' => [
            'image' => 'locations/locations-01',
            'intro' => [
                'Bashundhara Residential Area is one of the largest planned neighbourhoods in Dhaka, laid out in lettered blocks with wide roads, deep drains and plots reserved for schools, mosques and parks. It sits between Baridhara and Purbachal, close to the airport road and the 300 ft highway.',
                'Families choose Bashundhara for its universities, international schools and hospitals within a short drive, and for streets that stay quieter than the older parts of the city. Most buildings are six to nine storeys, which keeps the blocks green and open to the sky.',
                'Our projects here range from compact family apartments to garden-terrace homes and a mixed-use building with a market hall on the ground floor. Land in the newer blocks is still available as plots, so buyers can choose between a ready-built home and building their own. Prices have risen steadily as the metro and the expressway bring the area closer to central Dhaka.',
            ],
            'facts' => [['City', 'Dhaka'], ['To the airport', '20 min drive'], ['Main roads', 'Airport Road, 300 ft'], ['Typical apartment', '1,200–2,100 sft'], ['Universities nearby', '3'], ['Plot sizes', '3–10 katha']],
            'faq' => [
                ['Is Bashundhara R/A good for families?', '<p>Yes. The area has several universities and international schools, Evercare Hospital and large parks, with less through traffic than the older neighbourhoods.</p>'],
                ['How far is Bashundhara from Gulshan?', '<p>About 20 minutes by car outside peak hours, via Progati Sarani or the airport road.</p>'],
                ['Can I buy a plot instead of an apartment?', '<p>Yes. Our land projects in the newer blocks offer 3–5 katha plots with clear titles and roads already laid.</p>'],
            ],
            'nearby' => ['baridhara', 'gulshan', 'uttara', 'purbachal'],
            'segments' => [],
        ],
        'gulshan' => [
            'image' => 'locations/locations-02',
            'intro' => [
                'Gulshan is Dhaka’s diplomatic and business address: embassies, head offices, international restaurants and two lakes that frame the neighbourhood on the east and west. Gulshan 1 and Gulshan 2 circles anchor the shopping streets, while the residential roads behind them stay leafy and calm.',
                'Homes in Gulshan are larger than elsewhere in the city, typically three to five bedrooms with generous living rooms, staff quarters and two or more car parks. Buildings are low-density by Dhaka standards, and many face a lake or one of the parks. Most of the city’s international schools and private hospitals are a short drive away.',
                'Our Gulshan projects include four-bedroom lake-facing residences and a corner office tower on Gulshan Avenue. Demand comes from families who want to live close to work and schools, and from companies looking for a recognised address. Supply of new land is very limited, so well-located projects tend to sell before handover and keep their value over time.',
            ],
            'facts' => [['City', 'Dhaka'], ['To the airport', '25 min drive'], ['Lakes', 'Gulshan, Baridhara'], ['Typical apartment', '2,400–3,200 sft'], ['Character', 'Diplomatic and business']],
            'faq' => [
                ['Why are Gulshan apartments more expensive?', '<p>Land in Gulshan is scarce and buildings are low-density, so each apartment carries a larger share of land. Lake views and the business address add to the value.</p>'],
                ['Are there commercial spaces for sale in Gulshan?', '<p>Yes. Gemcon Gulshan Avenue offers office floors from 900 sft, with full generator back-up and basement parking.</p>'],
            ],
            'nearby' => ['banani', 'baridhara', 'bashundhara-ra'],
            'segments' => [],
        ],
        'banani' => [
            'image' => 'locations/locations-03',
            'intro' => [
                'Banani lies between Gulshan and the cantonment, with Kemal Ataturk Avenue as its commercial spine and Banani Lake along its eastern edge. In the last decade it has become one of Dhaka’s busiest office districts, with banks, tech companies and agencies taking whole floors in new towers.',
                'Behind the avenue, Banani’s residential roads are lined with restaurants, cafés and boutiques, which makes the area lively in the evening as well as during office hours. The railway station and the expressway put the airport and Uttara within easy reach.',
                'Our Banani projects are commercial: an eighteen-storey office and retail tower already under construction, and a new building with flexible floor plates and a public plaza. Both are designed for companies that want a professional address, efficient floors and reliable services. Smaller suites suit clinics, consultancies and showrooms, while full floors suit head offices that want their own reception and meeting rooms.',
            ],
            'facts' => [['City', 'Dhaka'], ['Main road', 'Kemal Ataturk Avenue'], ['To the airport', '20 min drive'], ['Office suites from', '850 sft'], ['Character', 'Offices, dining, retail']],
            'faq' => [
                ['Can I buy a single office suite in Banani?', '<p>Yes. Suites start at about 850 sft, and adjoining suites can be combined into a full floor.</p>'],
                ['Is parking included with office space?', '<p>Each suite comes with an allocated basement car park; larger units have two or more.</p>'],
            ],
            'nearby' => ['gulshan', 'baridhara', 'mohammadpur'],
            'segments' => [],
        ],
        'baridhara' => [
            'image' => 'locations/locations-04',
            'intro' => [
                'Baridhara is a quiet, low-rise diplomatic zone beside Gulshan, known for its lake, its parks and some of the largest apartments in Dhaka.',
            ],
            'facts' => [['City', 'Dhaka'], ['Character', 'Diplomatic, low-rise'], ['Typical apartment', '2,800–3,800 sft']],
            'faq' => [],
            'nearby' => ['gulshan', 'bashundhara-ra', 'banani'],
            'segments' => [],
        ],
        'dhanmondi' => [
            'image' => 'locations/locations-05',
            'intro' => [
                'Dhanmondi is one of Dhaka’s oldest planned residential areas, built around a long, winding lake with walking paths, trees and small parks on both banks. Its numbered roads mix family homes, schools, colleges, hospitals and a busy stretch of shops and restaurants around Road 27 and Satmasjid Road.',
                'People who grew up in Dhanmondi tend to stay, and many buyers come back to the area when they start a family. The schools and medical centres are within walking distance for most homes, and the lake gives the neighbourhood a calm centre that few parts of Dhaka can match.',
                'Our Dhanmondi projects include ready homes two minutes from the lake, a mixed-use building with apartments above a neighbourhood high street, and a lakeside building we have already handed over to its owners. Older houses are slowly being replaced by apartment buildings, so new homes here are scarce and rarely stay on the market for long.',
            ],
            'facts' => [['City', 'Dhaka'], ['Landmark', 'Dhanmondi Lake'], ['To Motijheel', '25 min drive'], ['Typical apartment', '1,900–2,500 sft'], ['Character', 'Established, family']],
            'faq' => [
                ['Are there ready apartments in Dhanmondi?', '<p>Yes. Gemcon Cedar Court is ready to move in, with a few units still available.</p>'],
                ['What is the parking situation in Dhanmondi?', '<p>Every apartment in our Dhanmondi buildings has at least one basement or ground-floor car park.</p>'],
            ],
            'nearby' => ['mohammadpur', 'gulshan', 'banani'],
            'segments' => [],
        ],
        'uttara' => [
            'image' => 'locations/locations-06',
            'intro' => [
                'Uttara is a planned satellite town in the north of Dhaka, divided into numbered sectors with their own parks, markets, schools and mosques. It sits right beside the airport and is the northern end of the metro line, which has made the daily journey into the city far easier than it used to be.',
                'The sectors are laid out on a grid with wide roads and lakes on the western side, and many have a central park within a few minutes’ walk of every home. Shopping malls, hospitals and colleges are spread across the town, so most errands never need a trip into central Dhaka.',
                'Our Uttara projects include ready apartments beside the Sector 7 park, new three- and four-bedroom homes in Sectors 11 and 13, gated residential plots and a retail and office podium next to the metro station. Prices are lower than in Gulshan or Banani for a similar size, which makes Uttara popular with first-time buyers and with families returning from abroad.',
            ],
            'facts' => [['City', 'Dhaka'], ['To the airport', '12 min drive'], ['Metro', 'Uttara North, Centre, South'], ['Typical apartment', '1,250–2,100 sft'], ['Sectors', '18'], ['Character', 'Planned, family']],
            'faq' => [
                ['How is the commute from Uttara to Motijheel?', '<p>The metro runs from Uttara North to Motijheel in about 40 minutes, avoiding road traffic entirely.</p>'],
                ['Which sectors are your Uttara projects in?', '<p>Sectors 7, 11 and 13 for apartments, a gated block for plots, and a site next to Uttara North station for retail and offices.</p>'],
                ['Are prices lower than in Gulshan?', '<p>Yes. A similar-sized apartment in Uttara usually costs less than half the Gulshan price, with more green space per home.</p>'],
            ],
            'nearby' => ['bashundhara-ra', 'mirpur', 'purbachal'],
            'segments' => [
                'residential' => '<p>Uttara’s residential sectors give families parks, schools and markets within walking distance, with the metro and the airport minutes away. Our apartments here range from 1,250 to 2,100 sft, from ready homes beside the Sector 7 park to new buildings in Sectors 11 and 13 with three- and four-bedroom layouts.</p>',
            ],
        ],
        'mirpur' => [
            'image' => 'locations/locations-04',
            'intro' => [
                'Mirpur is a large, fast-growing part of north-west Dhaka, close to the Botanical Garden and the Turag river, now linked to the centre by the metro.',
            ],
            'facts' => [['City', 'Dhaka'], ['Metro', 'Mirpur 10, 11'], ['Character', 'Growing, value']],
            'faq' => [],
            'nearby' => ['mohammadpur', 'uttara'],
            'segments' => [],
        ],
        'mohammadpur' => [
            'image' => 'locations/locations-02',
            'intro' => [
                'Mohammadpur is a central, well-connected neighbourhood west of Dhanmondi, with busy markets along Asad Avenue and quieter housing estates behind them.',
            ],
            'facts' => [['City', 'Dhaka'], ['Main road', 'Asad Avenue'], ['Typical apartment', '1,050–1,500 sft']],
            'faq' => [],
            'nearby' => ['dhanmondi', 'mirpur'],
            'segments' => [],
        ],
        'khulshi' => [
            'image' => 'locations/locations-05',
            'intro' => [
                'Khulshi is one of Chattogram’s most sought-after residential areas, set on low hills above the city with green slopes, winding roads and views towards the port. It is close to the university campuses, the GEC Circle shopping district and the main hospitals.',
                'Homes in Khulshi are spread across the hillsides, so many buildings have open views and more light and breeze than the flat parts of the city. The area is popular with families and with professionals who work in the port and the export industries.',
                'Our Khulshi projects include hillside apartments with views over Chattogram and a mixed-use building with homes and studios above a shopping arcade. Both are designed for the hilly terrain, with retaining walls and drainage planned from the start. Buyers from Dhaka and abroad often choose Khulshi as a second home close to the sea and the hills, or as a long-term investment in the country’s main port city.',
            ],
            'facts' => [['City', 'Chattogram'], ['To the airport', '35 min drive'], ['Terrain', 'Hillside'], ['Typical apartment', '1,350–1,700 sft'], ['Character', 'Green, residential']],
            'faq' => [
                ['Do your Khulshi apartments have views?', '<p>Most of them do. The buildings are placed on the slopes so that upper floors look over the city or the hills.</p>'],
            ],
            'nearby' => ['panchlaish'],
            'segments' => [],
        ],
        'panchlaish' => [
            'image' => 'locations/locations-03',
            'intro' => [
                'Panchlaish is a central Chattogram neighbourhood on CDA Avenue, home to offices, hospitals and a growing number of modern commercial buildings.',
            ],
            'facts' => [['City', 'Chattogram'], ['Main road', 'CDA Avenue'], ['Character', 'Business, central']],
            'faq' => [],
            'nearby' => ['khulshi'],
            'segments' => [],
        ],
        'purbachal' => [
            'image' => 'locations/locations-01',
            'intro' => [
                'Purbachal New Town is a large planned township east of Bashundhara, linked to Dhaka by the 300 ft road. Roads, lakes and utilities are being built ahead of the first neighbourhoods.',
            ],
            'facts' => [['City', 'Narayanganj'], ['Main road', '300 ft'], ['Character', 'New town, planned']],
            'faq' => [],
            'nearby' => ['bashundhara-ra', 'uttara'],
            'segments' => [],
        ],
    ];

    /**
     * @return array{lat: float, lng: float, zoom: int}
     */
    public static function centre(string $slug): array
    {
        [$lat, $lng, $zoom] = self::CENTRES[$slug];

        return ['lat' => $lat, 'lng' => $lng, 'zoom' => $zoom];
    }

    /**
     * @return DemoAreaDetail|null
     */
    public static function detail(string $slug): ?array
    {
        return self::DETAILS[$slug] ?? null;
    }

    /** Sanitised intro HTML (CMS rich text). */
    public static function introHtml(string $slug): string
    {
        return implode('', array_map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>', self::DETAILS[$slug]['intro']));
    }

    /**
     * PG-05/PG-06 copy (`{area}`, `{city}`, `{segment}` and `{count}` are
     * filled by the controller). Placeholder text until 12.1/13.2.
     *
     * @return array<string, string>
     */
    public static function copy(): array
    {
        return [
            'directory_title' => 'Locations',
            'directory_intro' => 'We build in the neighbourhoods people want to live and work in, from established addresses like Gulshan and Dhanmondi to growing towns in the north and east of Dhaka and the hills of Chattogram. Choose an area to see its projects, prices and what it is like to live there.',
            'directory_description' => 'Explore Gemcon City projects by location: apartments, commercial spaces and land in Dhaka, Chattogram and Narayanganj, area by area.',
            'area_title' => 'Flats and commercial spaces in {area}',
            'segment_title' => '{segment} in {area}',
            'segment_template' => '<p>{count} {segment_lower} projects by Gemcon City in {area}, {city}, with sizes, prices and handover dates on each project page.</p>',
            'facts_heading' => 'At a glance',
            'projects_heading' => 'Projects in {area}',
            'map_heading' => '{area} on the map',
            'nearby_heading' => 'Nearby areas',
            'faq_heading' => 'Questions about {area}',
            'empty_title' => 'No projects in {area} yet',
            'empty_body' => 'We are not building here at the moment. Here are our newest projects in other areas.',
            'newest_heading' => 'Newest projects',
            'no_projects' => 'No projects yet',
        ];
    }
}
