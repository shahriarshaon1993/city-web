<?php

namespace App\Queries\Site;

use App\Enums\FaqCategory;
use App\Enums\FaqPlacement;
use App\Models\FacetPage;
use App\Models\Faq;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The public reads of FAQs (FR-SOC-003, `FaqResource` in resources/js/types/faq.ts): published questions
 * only, in the order the editors dragged them into. A page lists the questions attached to it
 * (`faqables`); a question that is shown on "project pages" or "location pages" but attached to none
 * of them appears on every such page.
 */
final class FaqContent
{
    /**
     * PG-18: every question shown on the FAQ page, topic by topic.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function page(): array
    {
        return array_values(self::rows(self::published()->shownOn(FaqPlacement::FaqPage->value)->get())
            ->sortBy(fn (array $row): array => [FaqCategory::from($row['category'])->position(), $row['sort']])
            ->map(fn (array $row): array => self::item($row))->all());
    }

    /**
     * Topics that have questions on the FAQ page, in the spec's order, with their counts.
     *
     * @return list<array{slug: string, label: string, count: int}>
     */
    public static function categories(): array
    {
        $counts = array_count_values(array_column(self::page(), 'category'));

        return array_values(array_filter(array_map(
            fn (FaqCategory $category): array => ['slug' => $category->value, 'label' => $category->label(), 'count' => $counts[$category->value] ?? 0],
            FaqCategory::cases(),
        ), fn (array $category): bool => $category['count'] > 0));
    }

    /**
     * What a `faq` block shows: the questions of a topic, or the given ids in order. For the
     * landowner and NRB topics a question ticked "Landowner page" / "NRB page" counts too.
     *
     * @param  list<int>  $ids
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function forBlock(?string $category, array $ids = []): array
    {
        if ($category !== null && $category !== '') {
            $rows = self::published()
                ->where(fn (Builder $query) => $query->where('category', $category)->orWhereJsonContains('show_on', $category))
                ->get();

            return array_values(self::rows($rows)->sortBy('sort')->map(fn (array $row): array => self::item($row))->all());
        }

        if ($ids === []) {
            return [];
        }

        $found = self::rows(self::published()->whereKey($ids)->get())->keyBy('id');

        return array_values(array_filter(array_map(fn (int $id): ?array => isset($found[$id]) ? self::item($found[$id]) : null, $ids)));
    }

    /**
     * Topics a block can be filled from.
     *
     * @return list<string>
     */
    public static function blockCategories(): array
    {
        return array_map(fn (FaqCategory $category): string => $category->value, FaqCategory::cases());
    }

    /**
     * Published questions for the block form's "pick questions" list.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function all(): array
    {
        return array_values(self::rows(self::published()->get())->sortBy('sort')->map(fn (array $row): array => self::item($row))->all());
    }

    /**
     * PG-04: questions attached to the project, then the general ones for project pages.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function forProject(Project $project): array
    {
        return self::attached(Project::class, $project->id, FaqPlacement::ProjectDetail);
    }

    /**
     * PG-05/06: questions attached to the area, then the general ones for location pages.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function forArea(Location $area): array
    {
        return self::attached(Location::class, $area->id, FaqPlacement::Location);
    }

    /**
     * PG-03: only the questions attached to this listing page.
     *
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    public static function forFacet(FacetPage $facet): array
    {
        return array_values(self::rows(self::published()->whereHas('facetPages', fn (Builder $query) => $query->whereKey($facet->id))->get())
            ->sortBy('sort')->map(fn (array $row): array => self::item($row))->all());
    }

    /**
     * @param  class-string<Project|Location>  $model
     * @return list<array{id: int, question: string, answer: string, category: string}>
     */
    private static function attached(string $model, int $id, FaqPlacement $placement): array
    {
        $relation = $model === Project::class ? 'projects' : 'locations';
        $table = $model === Project::class ? 'projects' : 'locations';

        $own = self::published()->whereHas($relation, fn (Builder $query) => $query->where("{$table}.id", $id))->get();
        $general = self::published()->shownOn($placement->value)->doesntHave($relation)->get();

        return array_values(self::rows($own->merge($general)->unique('id'))->sortBy('sort')->map(fn (array $row): array => self::item($row))->all());
    }

    /**
     * @return Builder<Faq>
     */
    private static function published(): Builder
    {
        return Faq::query()->published()->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Collection<int, Faq>  $faqs
     * @return Collection<int, array{id: int, question: string, answer: string, category: string, sort: int}>
     */
    private static function rows(Collection $faqs): Collection
    {
        return $faqs->map(fn (Faq $faq): array => [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'category' => $faq->category->value,
            'sort' => $faq->sort_order * 100000 + $faq->id,
        ]);
    }

    /**
     * @param  array{id: int, question: string, answer: string, category: string, sort: int}  $row
     * @return array{id: int, question: string, answer: string, category: string}
     */
    private static function item(array $row): array
    {
        return ['id' => $row['id'], 'question' => $row['question'], 'answer' => $row['answer'], 'category' => $row['category']];
    }
}
