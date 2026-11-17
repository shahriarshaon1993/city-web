<?php

namespace App\Support\Demo;

/**
 * Project and area FAQs (answers are sanitised HTML). The catalogue moved to the database in 13.3,
 * but the `faqs` table only arrives in 16.4, so these placeholder questions stay here until then:
 * project FAQs by segment, area FAQs by area slug. Deleted in 16.4.
 */
final class DemoCatalogFaqs
{
    /**
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private const array AREAS = [
        'bashundhara-ra' => [
            ['Is Bashundhara R/A good for families?', '<p>Yes. The area has several universities and international schools, Evercare Hospital and large parks, with less through traffic than the older neighbourhoods.</p>'],
            ['How far is Bashundhara from Gulshan?', '<p>About 20 minutes by car outside peak hours, via Progati Sarani or the airport road.</p>'],
            ['Can I buy a plot instead of an apartment?', '<p>Yes. Our land projects in the newer blocks offer 3–5 katha plots with clear titles and roads already laid.</p>'],
        ],
        'gulshan' => [
            ['Why are Gulshan apartments more expensive?', '<p>Land in Gulshan is scarce and buildings are low-density, so each apartment carries a larger share of land. Lake views and the business address add to the value.</p>'],
            ['Are there commercial spaces for sale in Gulshan?', '<p>Yes. Gemcon Gulshan Avenue offers office floors from 900 sft, with full generator back-up and basement parking.</p>'],
        ],
        'banani' => [
            ['Can I buy a single office suite in Banani?', '<p>Yes. Suites start at about 850 sft, and adjoining suites can be combined into a full floor.</p>'],
            ['Is parking included with office space?', '<p>Each suite comes with an allocated basement car park; larger units have two or more.</p>'],
        ],
        'dhanmondi' => [
            ['Are there ready apartments in Dhanmondi?', '<p>Yes. Gemcon Cedar Court is ready to move in, with a few units still available.</p>'],
            ['What is the parking situation in Dhanmondi?', '<p>Every apartment in our Dhanmondi buildings has at least one basement or ground-floor car park.</p>'],
        ],
        'uttara' => [
            ['How is the commute from Uttara to Motijheel?', '<p>The metro runs from Uttara North to Motijheel in about 40 minutes, avoiding road traffic entirely.</p>'],
            ['Which sectors are your Uttara projects in?', '<p>Sectors 7, 11 and 13 for apartments, a gated block for plots, and a site next to Uttara North station for retail and offices.</p>'],
            ['Are prices lower than in Gulshan?', '<p>Yes. A similar-sized apartment in Uttara usually costs less than half the Gulshan price, with more green space per home.</p>'],
        ],
        'khulshi' => [
            ['Do your Khulshi apartments have views?', '<p>Most of them do. The buildings are placed on the slopes so that upper floors look over the city or the hills.</p>'],
        ],
    ];

    /**
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private const array SEGMENTS = [
        'land' => [
            ['Are the plot titles clear?', '<p>Yes. Every plot has a registered deed and completed mutation, and we hand over the full chain of title documents for your lawyer to check before booking.</p>'],
            ['Can I combine two plots?', '<p>Adjoining plots can be bought together and registered as one, subject to RAJUK rules for the combined size.</p>'],
            ['When are the utilities connected?', '<p>Roads, drainage and water lines are complete before handover. Electricity and gas connections are applied for in the owner\'s name with our help.</p>'],
        ],
        'commercial' => [
            ['Can I buy a whole floor?', '<p>Yes. Floors can be bought whole or as suites, and adjoining suites can be joined later without structural work.</p>'],
            ['Is there parking for visitors?', '<p>Each unit comes with allocated basement parking, and part of the ground-floor parking is kept for visitors.</p>'],
            ['When can I start the fit-out?', '<p>Fit-out can start once the floor is handed over; our team shares the services drawings and fit-out guidelines in advance.</p>'],
        ],
        'residential' => [
            ['What is the payment schedule?', '<p>A booking money payment is followed by a down payment and instalments linked to construction progress. The sales team shares the full schedule for each unit type.</p>'],
            ['Is the project approved by RAJUK?', '<p>Yes. The approval number is listed under At a glance, and copies of the approval are available at our sales office.</p>'],
            ['Can I buy from abroad?', '<p>Yes. Many of our buyers are non-resident Bangladeshis. We arrange video site visits, send documents by email and accept payments through bank channels.</p>'],
        ],
        'mixed_use' => [
            ['What is the payment schedule?', '<p>A booking money payment is followed by a down payment and instalments linked to construction progress. The sales team shares the full schedule for each unit type.</p>'],
            ['Is the project approved by RAJUK?', '<p>Yes. The approval number is listed under At a glance, and copies of the approval are available at our sales office.</p>'],
            ['Can I buy from abroad?', '<p>Yes. Many of our buyers are non-resident Bangladeshis. We arrange video site visits, send documents by email and accept payments through bank channels.</p>'],
            ['Are the shops sold separately?', '<p>Yes. Shops on the ground and first floors are sold separately from the apartments and have their own entrance.</p>'],
        ],
    ];

    /**
     * @return list<array{id: int, question: string, answer: string}>
     */
    public static function project(string $segment, int $projectId): array
    {
        return self::rows(self::SEGMENTS[$segment] ?? [], $projectId * 100);
    }

    /**
     * @return list<array{id: int, question: string, answer: string}>
     */
    public static function area(string $slug): array
    {
        return self::rows(self::AREAS[$slug] ?? [], 0);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items
     * @return list<array{id: int, question: string, answer: string}>
     */
    private static function rows(array $items, int $offset): array
    {
        return array_map(
            fn (array $item, int $index): array => ['id' => $offset + $index + 1, 'question' => $item[0], 'answer' => $item[1]],
            $items,
            array_keys($items),
        );
    }
}
