<?php

namespace Database\Seeders;

use App\Enums\FaqCategory;
use App\Enums\FaqPlacement;
use App\Models\FacetPage;
use App\Models\Faq;
use App\Models\Location;
use App\Models\PageBlock;
use App\Models\Project;
use Database\Seeders\Data\FacetSeedData;
use Database\Seeders\Data\FaqSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The placeholder FAQs of the client's review (outside production only): the FAQ page's questions, the
 * questions of the builder pages, and the questions attached to areas, project segments and the
 * listing landing pages. Run it twice, like the page seeder: `run()` creates the rows, `attach()`
 * (after the catalogue is seeded) links the ones that belong to single pages. Safe to run again.
 */
class FaqSeeder extends Seeder
{
    /** Builder-page question groups and the FAQ category each one is filed under. */
    public const array PAGE_CATEGORIES = [
        'page_services' => FaqCategory::General,
        'page_landowner' => FaqCategory::Landowner,
        'page_nrb' => FaqCategory::Nrb,
        'page_buyer_guide' => FaqCategory::Buying,
    ];

    public function run(): void
    {
        foreach (FaqSeedData::ITEMS as [$category, $question, $answer]) {
            $this->faq($question, $answer, FaqCategory::from($category), [FaqPlacement::FaqPage->value]);
        }

        foreach (FaqSeedData::PAGE_ITEMS as $group => $items) {
            foreach ($items as [$question, $answer]) {
                $this->faq($question, $answer, self::PAGE_CATEGORIES[$group], []);
            }
        }

        $this->repairPageBlocks();
    }

    /**
     * Before 16.4 a seeded page's `faq` block named a demo group (`page_services`…) as its category. Those
     * blocks now pick the group's questions by id, like a freshly seeded page does.
     */
    private function repairPageBlocks(): void
    {
        foreach (PageBlock::query()->where('type', 'faq')->get() as $block) {
            $category = (string) ($block->data['category'] ?? '');

            if (str_starts_with($category, 'page_')) {
                $block->update(['data' => [...$block->data, 'source' => 'manual', 'ids' => self::pageFaqIds($category), 'category' => null]]);
            }
        }
    }

    /**
     * The ids of a builder page's questions, in order (the page seeder points its `faq` block at them).
     *
     * @return list<int>
     */
    public static function pageFaqIds(string $group): array
    {
        $questions = array_column(FaqSeedData::PAGE_ITEMS[$group] ?? [], 0);

        if ($questions === []) {
            return [];
        }

        $ids = Faq::query()->whereIn('question', $questions)->pluck('id', 'question');

        return array_values(array_filter(array_map(fn (string $question): ?int => $ids[$question] ?? null, $questions)));
    }

    /** Attaches the area, segment and landing-page questions once their pages exist. */
    public function attach(): void
    {
        foreach (FaqSeedData::AREAS as $slug => $items) {
            $area = Location::query()->where('slug', $slug)->first();

            foreach ($items as [$question, $answer]) {
                $faq = $this->faq($question, $answer, FaqCategory::General, [FaqPlacement::Location->value]);

                if ($area !== null) {
                    $this->link($faq, $area->getMorphClass(), $area->id);
                }
            }
        }

        foreach (FaqSeedData::SEGMENTS as $segment => $items) {
            $projects = Project::query()->where('segment', $segment)->pluck('id');

            foreach ($items as [$question, $answer]) {
                $faq = $this->faq($question, $answer, FaqCategory::Buying, [FaqPlacement::ProjectDetail->value]);

                foreach ($projects as $projectId) {
                    $this->link($faq, (new Project)->getMorphClass(), (int) $projectId);
                }
            }
        }

        foreach (FacetSeedData::all() as $key => $facet) {
            [$kind, $value] = explode(':', $key);
            $page = FacetPage::query()->where('kind', $kind)->where('value', $value)->first();

            foreach ($facet['faq'] as [$question, $answer]) {
                $faq = $this->faq($question, $answer, FaqCategory::Buying, []);

                if ($page !== null) {
                    $this->link($faq, $page->getMorphClass(), $page->id);
                }
            }
        }
    }

    /**
     * @param  list<string>  $showOn
     */
    private function faq(string $question, string $answer, FaqCategory $category, array $showOn): Faq
    {
        return Faq::withTrashed()->firstOrCreate(
            ['question' => $question],
            [
                'answer' => $answer,
                'category' => $category,
                'show_on' => $showOn,
                'sort_order' => ((int) Faq::withTrashed()->max('sort_order')) + 1,
                'is_published' => true,
            ],
        );
    }

    private function link(Faq $faq, string $type, int $id): void
    {
        DB::table('faqables')->insertOrIgnore([
            'faq_id' => $faq->id,
            'faqable_type' => $type,
            'faqable_id' => $id,
            'sort_order' => ((int) DB::table('faqables')->where('faq_id', $faq->id)->max('sort_order')) + 1,
        ]);
    }
}
