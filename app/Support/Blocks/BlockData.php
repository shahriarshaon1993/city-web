<?php

namespace App\Support\Blocks;

use App\Queries\Site\AreaDirectory;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoFaqs;
use App\Support\Demo\DemoNews;
use App\Support\Demo\DemoTeam;

/**
 * Where block schemas read the content of entities that are not page data: testimonials, partners,
 * posts, team members, FAQs and offices. Projects and areas already come from their tables; the
 * rest still come from the demo content until their admin arrives (16.3 posts, 16.4 the others),
 * so only this class changes when each moves to its table (ARC-002).
 */
final class BlockData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function testimonials(): array
    {
        return DemoContent::testimonials();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function partners(): array
    {
        return DemoContent::partners();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function news(?string $category, int $limit): array
    {
        return DemoNews::cards($category, $limit);
    }

    /**
     * @return list<string> the category slugs a news block can filter by
     */
    public static function newsCategories(): array
    {
        return array_column(DemoNews::categories(), 'slug');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function team(string $group): array
    {
        return DemoTeam::members($group);
    }

    /**
     * FAQ rows (`id`, `question`, `answer`, `category`) of a category, or the given ids in order.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public static function faqs(?string $category, array $ids = []): array
    {
        return DemoFaqs::forBlock($category, $ids);
    }

    /**
     * @return list<string>
     */
    public static function faqCategories(): array
    {
        return DemoFaqs::blockCategories();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function offices(): array
    {
        return DemoContent::offices();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function progressStages(): array
    {
        return DemoContent::progressStages();
    }

    /**
     * @param  list<int>  $projectIds
     * @return list<array<string, mixed>>
     */
    public static function progressSummaries(array $projectIds): array
    {
        return DemoContent::progressSummaries($projectIds);
    }

    /**
     * The hero search widget's options (CMP-SEARCH-01).
     *
     * @return array<string, mixed>
     */
    public static function projectSearch(): array
    {
        return DemoContent::projectSearch();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function areas(): array
    {
        return AreaDirectory::all();
    }
}
