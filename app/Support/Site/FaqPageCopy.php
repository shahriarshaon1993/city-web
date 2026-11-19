<?php

namespace App\Support\Site;

use App\Support\Blocks\BlockEnvelope;
use App\Support\Seo\Breadcrumbs;
use App\Support\Settings\Settings;

/**
 * Labels and copy of the FAQ page PG-18 (CMS-000a) and its "Still have questions?" band (LAY-FAQ). The
 * questions themselves come from the `faqs` table.
 */
final class FaqPageCopy
{
    /**
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

    /**
     * @return array<string, mixed>
     */
    public static function cta(): array
    {
        return BlockEnvelope::make(2, 'cta_band', [
            'heading' => 'Still have questions?',
            'text' => 'Our team answers by phone, WhatsApp or email, usually within one working day.',
            'primary_cta' => ['label' => 'Contact us', 'url' => '/contact', 'new_tab' => false],
            'secondary_cta' => ['label' => 'Call the hotline', 'url' => 'tel:'.Settings::group('contact')['hotline'], 'new_tab' => false],
            'style' => 'gradient',
            'image' => null,
        ], background: 'dark', spacing: 'lg');
    }
}
