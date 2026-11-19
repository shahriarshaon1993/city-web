<?php

namespace Database\Seeders\Data;

/**
 * Placeholder offices, testimonials, partners and team members for the client's review (outside
 * production only). Fictional people and firms; the contact details are the spec placeholders (DEC-17).
 */
final class SocialSeedData
{
    /**
     * DM-OFF rows (spec/social.md); placeholder addresses until the client's arrive.
     *
     * @var list<array<string, mixed>>
     */
    public const array OFFICES = [
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
     * DM-TEST rows (FR-SOC-001): plain-text quotes, no rating. Fictional
     * placeholders; no photos until the client supplies them (DEC-47).
     *
     * @var list<array{quote: string, person_name: string, designation: string|null, project: string|null, is_nrb: bool}>
     */
    public const array TESTIMONIALS = [
        ['quote' => 'We booked from Toronto without ever visiting the site. The monthly progress photos and a sales team that answered every email at odd hours made the decision easy. The flat was handed over on the promised date.', 'person_name' => 'Farhana Rahman', 'designation' => 'Software engineer, Toronto', 'project' => 'gemcon-greenfield', 'is_nrb' => true],
        ['quote' => 'Clear papers, a RAJUK-approved plan and no surprises in the final bill. That is rare in this market.', 'person_name' => 'Mahmudul Hasan', 'designation' => 'Apartment owner', 'project' => 'gemcon-palm-court', 'is_nrb' => false],
        ['quote' => 'We gave our family land in a joint venture. Gemcon explained every clause, kept us updated at each stage and delivered our share of flats exactly as agreed.', 'person_name' => 'Nasreen Akter', 'designation' => 'Landowner', 'project' => null, 'is_nrb' => false],
        ['quote' => 'Our office floor at the Trade Centre was ready for fit-out a month early. The building management team is quick and professional.', 'person_name' => 'Tanvir Ahmed', 'designation' => 'Managing director, logistics firm', 'project' => 'gemcon-trade-centre', 'is_nrb' => false],
        ['quote' => 'After-sales service is what sold us on a second apartment. Small snags after handover were fixed within a week, without us having to chase anyone.', 'person_name' => 'Shirin Sultana', 'designation' => 'Homeowner since 2019', 'project' => 'gemcon-greenfield', 'is_nrb' => false],
        ['quote' => 'Buying from Dubai felt risky until our first video site visit. The engineer walked us through the structure floor by floor and answered every question.', 'person_name' => 'Kamrul Islam', 'designation' => 'Engineer, Dubai', 'project' => 'gemcon-lakeview', 'is_nrb' => true],
    ];

    /**
     * DM-PART rows (FR-SOC-002); the logos are the fictional wordmarks of
     * the partner wordmarks in `storage/app/demo-src/partners`, in the same order.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array PARTNERS = [
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

    /**
     * name, role, bio, portrait, LinkedIn.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string|null, 3: int, 4: bool}>>
     */
    public const array MEMBERS = [
        'leadership' => [
            ['Rashed Karim', 'Chairman', 'Founded Gemcon City after two decades in construction finance. He still reviews every land purchase himself and signs each handover certificate.', 1, true],
            ['Farhana Rahman', 'Managing Director', 'Leads planning, sales and after-sales. Trained as an architect, she insists that every plan is walked on site before it is launched.', 2, true],
            ['Tanvir Hossain', 'Director, Projects', 'Oversees engineering and construction across all active sites, with a focus on tested materials and published inspection stages.', 3, true],
        ],
        'management' => [
            ['Nasrin Akter', 'Head of Legal', null, 4, true],
            ['Imran Chowdhury', 'Chief Engineer', null, 5, true],
            ['Sadia Islam', 'Head of Sales', null, 6, false],
            ['Mahmud Alam', 'Head of Finance', null, 1, true],
        ],
        'team' => [
            ['Rubina Sultana', 'Customer Care Lead', null, 2, false],
            ['Arif Hasan', 'Site Engineer', null, 3, false],
            ['Mim Khatun', 'Sales Advisor', null, 4, true],
            ['Shakil Ahmed', 'Architect', null, 5, true],
            ['Lamia Noor', 'Interior Designer', null, 6, false],
            ['Jahid Mia', 'Land Acquisition Officer', null, 1, false],
        ],
    ];
}
