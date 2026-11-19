<?php

namespace Database\Seeders\Data;

/**
 * The placeholder text of the eight DM-FACET landing pages (title, intro, meta description and the
 * questions attached to each), seeded in every environment; the client edits it in the admin.
 */
final class FacetSeedData
{
    /**
     * @return array<string, array{title: string, intro: string, description: string, faq: list<array{0: string, 1: string}>}>
     */
    public static function all(): array
    {
        $facet = fn (string $title, string $intro, string $description, array $faq = []): array => [
            'title' => $title,
            'intro' => $intro,
            'description' => $description,
            'faq' => $faq,
        ];

        return [
            'segment:residential' => $facet(
                'Residential projects in Bangladesh',
                'Family apartments from two to five bedrooms, built to RAJUK-approved designs with clear titles. Every project lists its sizes, handover date and monthly construction progress, so you can compare homes before you visit the site.',
                'Apartments for sale in Dhaka and Chattogram by Gemcon City: ongoing, upcoming and ready residential projects with sizes, prices and handover dates.',
                [
                    ['Can I book an apartment from abroad?', '<p>Yes. Most of our NRB buyers book by video site visit and a power of attorney. Our sales team sends the documents, payment schedule and progress photos by email.</p>'],
                    ['What is included in the apartment price?', '<p>The price covers the apartment, its share of land and a car park where stated. Registration costs and utility connection fees are listed separately in the price sheet.</p>'],
                    ['How do instalments work?', '<p>A booking money payment is followed by instalments linked to construction stages. The schedule for each project is in its brochure.</p>'],
                ],
            ),
            'segment:commercial' => $facet(
                'Commercial projects in Bangladesh',
                'Office floors, retail spaces and showrooms in Banani, Gulshan, Uttara and Chattogram, with flexible floor plates and professional building management.',
                'Office and retail spaces for sale by Gemcon City in Dhaka and Chattogram, from single suites to full floors.',
            ),
            'segment:mixed_use' => $facet(
                'Mixed-use projects in Bangladesh',
                'Homes above shops, markets and offices: everyday errands downstairs and a quiet apartment upstairs.',
                'Mixed-use developments by Gemcon City combining apartments with retail and office space in Dhaka and Chattogram.',
            ),
            'segment:land' => $facet(
                'Land projects in Bangladesh',
                'Residential plots with clear titles, mutation completed and roads, drainage and utilities laid before handover.',
                'Plots for sale by Gemcon City in Dhaka with clear titles, planned roads and utilities.',
            ),
            'status:upcoming' => $facet(
                'Upcoming projects in Bangladesh',
                'Projects that are approved and open for early booking, often at launch prices before construction starts.',
                'Upcoming Gemcon City projects open for early booking in Dhaka and Chattogram.',
            ),
            'status:ongoing' => $facet(
                'Ongoing projects in Bangladesh',
                'Projects under construction, with progress reports every month and handover dates we publish and keep.',
                'Gemcon City projects under construction in Dhaka and Chattogram, with monthly progress updates and handover dates.',
                [
                    ['How do I follow construction progress?', '<p>Every ongoing project page shows the latest progress report with photos and the percentage complete. Buyers also receive the report by email each month.</p>'],
                    ['Can I visit the site during construction?', '<p>Yes. Book a site visit and an engineer will walk you through the building with safety equipment.</p>'],
                ],
            ),
            'status:ready' => $facet(
                'Ready apartments and spaces in Bangladesh',
                'Completed projects with units still available: move in or start your fit-out within weeks of booking.',
                'Ready-to-move apartments and commercial spaces by Gemcon City with units available now.',
            ),
            'status:handed_over' => $facet(
                'Handed-over projects',
                'Buildings we have completed and handed over to their owners, a record of the promises we kept.',
                'Completed and handed-over Gemcon City projects in Dhaka and Chattogram.',
            ),
        ];
    }
}
