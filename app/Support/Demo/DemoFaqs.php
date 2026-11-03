<?php

namespace App\Support\Demo;

use App\Support\Seo\Breadcrumbs;

/**
 * Part A FAQs (FR-SOC-003, DM-FAQ): the questions shown on PG-18, in the
 * spec's category order, shaped like the later FaqResource
 * (resources/js/types/faq.ts). Answers are the limited rich text of
 * FR-SOC-003 (paragraph, bold, link, list). Placeholder copy for the
 * client's review; deleted with DemoContent in 16.4.
 */
final class DemoFaqs
{
    /** FR-SOC-003 categories, in display order. */
    public const array CATEGORIES = [
        'buying' => 'Buying',
        'payment' => 'Payments',
        'legal' => 'Legal and documents',
        'nrb' => 'Buying from abroad',
        'landowner' => 'Landowners',
        'after_sales' => 'After-sales',
        'general' => 'General',
    ];

    /**
     * [category, question, answer] — every row has `faq` in show_on.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const array ITEMS = [
        ['buying', 'How do I book an apartment?', '<p>Choose the project and unit with our sales team, sign the booking form and pay the booking money. You receive a money receipt the same day and the allotment letter within two weeks.</p>'],
        ['buying', 'Can I visit a project before I book?', '<p>Yes. <a href="/projects">Pick a project</a> and use <strong>Book a site visit</strong>, or call the hotline. Visits run every day, including Fridays, with an engineer or sales advisor on site.</p>'],
        ['buying', 'What is included in the apartment price?', '<p>The price covers the apartment, its share of the land and the common areas, and the finishes listed in the specification. Car parking, the utility connection fees and registration costs are quoted separately.</p>'],
        ['buying', 'How is the size of an apartment measured?', '<p>Sizes are stated in square feet (sft) and include a proportional share of the common areas such as stairs, lift lobbies and corridors. The usable floor area is shown on each floor plan.</p>'],
        ['payment', 'Which payment plans do you offer?', '<p>Most projects offer:</p><ul><li>a one-time payment with a discount,</li><li>instalments linked to construction stages,</li><li>monthly instalments over the construction period.</li></ul><p>The plan for each project is in its brochure.</p>'],
        ['payment', 'Can I take a home loan?', '<p>Yes. Several banks and financial institutions offer home loans for our projects. Our team can introduce you to them and provide the documents the bank asks for.</p>'],
        ['payment', 'What happens if I miss an instalment?', '<p>Talk to us first: we can usually reschedule one instalment. Repeated late payments carry a delay charge, as set out in the agreement.</p>'],
        ['payment', 'Do you accept payments in foreign currency?', '<p>Buyers abroad can pay by bank transfer to our account in Bangladesh. The amount is credited in taka at the bank’s rate on the day it arrives.</p>'],
        ['legal', 'How do I know the land title is clear?', '<p>Before we start any project our legal team checks the deeds, mutation, tax records and court records for the land. Copies of the title documents are available to buyers before booking.</p>'],
        ['legal', 'Is the project approved by RAJUK?', '<p>Every project is built to an approved plan. The approval number and date are shown on the project page and in the brochure, and you can check them with the authority.</p>'],
        ['legal', 'When is the apartment registered in my name?', '<p>Registration follows handover and the final payment. Our legal team prepares the deed and books the appointment at the sub-registry office; registration fees and taxes are paid by the buyer.</p>'],
        ['nrb', 'Can I buy if I live outside Bangladesh?', '<p>Yes. Many of our buyers live abroad. You can choose a unit by video call, sign the booking documents through a power of attorney and pay by bank transfer. See <a href="/nrb">buying from abroad</a>.</p>'],
        ['nrb', 'Do I need to come to Bangladesh to sign?', '<p>No. A family member or another person you trust can sign for you with a power of attorney attested by the Bangladesh mission in your country.</p>'],
        ['nrb', 'How will I follow construction progress?', '<p>Project pages show monthly progress photos and the current construction stage. Your sales advisor can also arrange a video walk-through of the site.</p>'],
        ['landowner', 'What size of plot do you develop?', '<p>Plots of five katha and above are usually suitable, depending on the road width and the approved floor-area ratio. <a href="/landowner">Share your plot details</a> and our land team will advise you.</p>'],
        ['landowner', 'What share of the building does the landowner receive?', '<p>The share depends on the plot, its location and the approved design. We propose a figure after the site and title review and write it into the joint-venture agreement.</p>'],
        ['after_sales', 'What happens at handover?', '<p>You inspect the apartment with our engineer, note anything to fix and receive the keys, the user manual and the warranty documents once the list is closed.</p>'],
        ['after_sales', 'Is there a warranty?', '<p>Structural work is covered for ten years and fittings for one year from handover. The warranty terms are part of your handover documents.</p>'],
        ['after_sales', 'Who manages the building after handover?', '<p>Our facility team runs the building for the first year while the owners’ association is formed, then hands over the accounts and contracts to it.</p>'],
        ['general', 'Where are your offices?', '<p>Our head office is in Gulshan 2 and the sales lounge is in Bashundhara R/A. Addresses, hours and maps are on the <a href="/contact">contact page</a>.</p>'],
        ['general', 'How can I reach you outside office hours?', '<p>Send a message on WhatsApp or use <strong>Request a call back</strong>, and we will call you the next working day at the time you choose.</p>'],
    ];

    /**
     * FaqResource rows (types/faq.ts), in category order.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function items(): array
    {
        $order = array_flip(array_keys(self::CATEGORIES));
        $items = array_map(
            fn (array $row, int $index): array => ['id' => $index + 1, 'question' => $row[1], 'answer' => $row[2], 'category' => $row[0]],
            self::ITEMS,
            array_keys(self::ITEMS),
        );

        usort($items, fn (array $a, array $b): int => [$order[$a['category']], $a['id']] <=> [$order[$b['category']], $b['id']]);

        return $items;
    }

    /**
     * Categories that have questions, with their counts.
     *
     * @return list<array{slug: string, label: string, count: int}>
     */
    public static function categories(): array
    {
        $counts = array_count_values(array_column(self::ITEMS, 0));

        return array_values(array_filter(array_map(
            fn (string $slug, string $label): array => ['slug' => $slug, 'label' => $label, 'count' => $counts[$slug] ?? 0],
            array_keys(self::CATEGORIES),
            array_values(self::CATEGORIES),
        ), fn (array $category): bool => $category['count'] > 0));
    }

    /**
     * LAY-FAQ "Still have questions?" band.
     *
     * @return array<string, mixed>
     */
    public static function cta(): array
    {
        return DemoBlocks::block(2, 'cta_band', [
            'heading' => 'Still have questions?',
            'text' => 'Our team answers by phone, WhatsApp or email, usually within one working day.',
            'primary_cta' => ['label' => 'Contact us', 'url' => '/contact', 'new_tab' => false],
            'secondary_cta' => ['label' => 'Call the hotline', 'url' => 'tel:'.DemoContent::settings('contact')['hotline'], 'new_tab' => false],
            'style' => 'gradient',
            'image' => null,
        ], background: 'dark', spacing: 'lg');
    }

    /**
     * Labels and copy of PG-18 (CMS-000a).
     *
     * @return array<string, mixed>
     */
    public static function copy(): array
    {
        $title = 'Frequently asked questions';

        return [
            'title' => $title,
            'intro' => 'Answers about buying, payments, documents and life after handover. Search, or pick a topic.',
            'description' => 'Answers to common questions about buying property with Gemcon City Limited: booking, payment plans, home loans, land titles, registration, buying from abroad and after-sales service.',
            'search_label' => 'Search the questions',
            'search_placeholder' => 'e.g. home loan, registration',
            'topics_label' => 'Topics',
            'all_label' => 'All topics',
            'results_count' => '{n} questions',
            'results_count_one' => '1 question',
            'empty_title' => 'No questions match “{q}”',
            'empty_body' => 'Try another word, or ask us directly.',
            'empty_all_action' => 'Search all topics',
            'empty_clear_action' => 'Clear search',
            'breadcrumbs' => Breadcrumbs::to($title),
        ];
    }
}
