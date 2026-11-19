<?php

namespace App\Support\Blocks;

use App\Models\Office;
use App\Models\PostCategory;
use App\Queries\Site\AreaDirectory;
use App\Queries\Site\FaqContent;
use App\Queries\Site\NewsQuery;
use App\Queries\Site\ProgressSummaries;
use App\Queries\Site\ProjectSearchOptions;
use App\Queries\Site\SocialContent;
use App\Support\Catalog\CatalogCopy;

/**
 * Where block schemas read the content of entities that are not page data: testimonials, partners,
 * posts, team members, FAQs, offices, progress and the search options. Every one comes from its table
 * (ARC-002), so this class is the single seam between the blocks and the data.
 */
final class BlockData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function testimonials(): array
    {
        return SocialContent::testimonials();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function partners(): array
    {
        return SocialContent::partners();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function news(?string $category, int $limit): array
    {
        return NewsQuery::cards($category, $limit);
    }

    /**
     * @return list<string> the category slugs a news block can filter by
     */
    public static function newsCategories(): array
    {
        return array_values(PostCategory::query()->orderBy('sort_order')->pluck('slug')->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function team(string $group): array
    {
        return SocialContent::team($group);
    }

    /**
     * FAQ rows (`id`, `question`, `answer`, `category`) of a category, or the given ids in order.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public static function faqs(?string $category, array $ids = []): array
    {
        return FaqContent::forBlock($category, $ids);
    }

    /**
     * Every published question, for the block form's picker.
     *
     * @return list<array<string, mixed>>
     */
    public static function allFaqs(): array
    {
        return FaqContent::all();
    }

    /**
     * @return list<string>
     */
    public static function faqCategories(): array
    {
        return FaqContent::blockCategories();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function offices(): array
    {
        return SocialContent::offices();
    }

    /**
     * Every office, for the block form's picker (an office has no published switch).
     *
     * @return list<array{id: int, name: string}>
     */
    public static function officeChoices(): array
    {
        return array_values(Office::query()->orderByDesc('is_head_office')->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])
            ->map(fn (Office $office): array => ['id' => $office->id, 'name' => $office->name])->all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function progressStages(): array
    {
        return CatalogCopy::progressStages();
    }

    /**
     * @param  list<int>  $projectIds
     * @return list<array<string, mixed>>
     */
    public static function progressSummaries(array $projectIds): array
    {
        return ProgressSummaries::latest($projectIds);
    }

    /**
     * The hero search widget's options (CMP-SEARCH-01).
     *
     * @return array<string, mixed>
     */
    public static function projectSearch(): array
    {
        return ProjectSearchOptions::all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function areas(): array
    {
        return AreaDirectory::all();
    }
}
