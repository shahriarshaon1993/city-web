<?php

namespace Database\Seeders;

use App\Actions\Pages\SavePage;
use App\Actions\Publishing\PublishEntity;
use App\Enums\Lifecycle;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Project;
use Database\Seeders\Support\ImportSeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Creates the system pages in the order of 6.3 — about, leadership, services, landowner, nrb, the legal
 * pages, then home and careers — from the block lists in `database/seeders/pages/*.json` (the layouts
 * of design/layouts.md with placeholder copy for the client's review), and publishes each one whose
 * checklist passes. Outside production it also creates the two demo custom pages.
 *
 * The JSON holds the editor shape of the blocks; a photo is `{"demo": "group/name"}` and becomes a
 * library asset (`ImportSeedMedia`), so without the demo photos those blocks stay incomplete and the
 * page stays a draft. Safe to run again: an existing page is never changed, except that a seeded page
 * nobody has edited is published once its blocks are complete (the catalogue is seeded after it).
 */
class PageSeeder extends Seeder
{
    /** Menus created from the demo order point at these ids (about = 1 … terms = 9). */
    private const array ORDER = ['about', 'leadership', 'services', 'landowner', 'nrb', 'sustainability', 'buyer-guide', 'privacy-policy', 'terms', 'home', 'careers'];

    private const array DEMO_ONLY = ['sustainability', 'buyer-guide'];

    public function run(SavePage $savePage, PublishEntity $publish, ImportSeedMedia $importMedia): void
    {
        foreach (self::ORDER as $slug) {
            if (app()->isProduction() && in_array($slug, self::DEMO_ONLY, true)) {
                continue;
            }

            $page = Page::withTrashed()->where('slug', $slug)->first();

            if ($page === null) {
                $page = DB::transaction(fn (): Page => $this->create($savePage, $importMedia, $slug));
            } elseif ($page->trashed() || $page->publish_status !== PublishStatus::Draft || $page->edited_by !== null) {
                continue;
            }

            $this->publish($publish, $page);
        }
    }

    private function create(SavePage $savePage, ImportSeedMedia $importMedia, string $slug): Page
    {
        /** @var array<string, mixed> $source */
        $source = json_decode(File::get(database_path("seeders/pages/{$slug}.json")), true, flags: JSON_THROW_ON_ERROR);

        $page = Page::query()->create([
            'title' => $source['title'], 'slug' => $slug, 'template' => $source['template'],
            'is_system' => (bool) $source['system'], 'publish_status' => PublishStatus::Draft,
        ]);

        $blocks = array_map(function (array $block) use ($importMedia): array {
            $block['data'] = $this->withAssetIds($block['data'], $importMedia);

            // The questions of one page are FAQ rows picked by id (a `page_*` group of the FAQ seeder).
            if ($block['type'] === 'faq' && str_starts_with((string) ($block['data']['category'] ?? ''), 'page_')) {
                $block['data'] = [...$block['data'], 'source' => 'manual', 'ids' => FaqSeeder::pageFaqIds($block['data']['category']), 'category' => null];
            }

            if ($block['type'] === 'progress_highlight') {
                $block['data']['project_ids'] = $this->progressProjects();
            }

            return $block;
        }, $source['blocks']);

        $seo = $source['description'] === null && $source['index'] ? null : [
            'meta_description' => $source['description'],
            'robots' => $source['index'] ? 'index,follow' : 'noindex,follow',
        ];

        return $savePage->handle(null, [
            'title' => $source['title'],
            'slug' => $slug,
            'template' => $source['template'],
            'last_reviewed_at' => $source['reviewed'],
            'blocks' => $blocks,
            'seo' => $seo,
        ], $page, false);
    }

    private function publish(PublishEntity $publish, Page $page): void
    {
        try {
            $publish->handle(null, $page);
        } catch (ValidationException $exception) {
            $this->command?->warn("Page “{$page->slug}” stays a draft: ".implode(' ', $exception->validator->errors()->all()));
        }
    }

    /**
     * `{"demo": "hero/hero-02"}` → the library asset id (null while the demo photo is missing).
     *
     * @param  array<int|string, mixed>  $node
     * @return array<int|string, mixed>
     */
    private function withAssetIds(array $node, ImportSeedMedia $importMedia): array
    {
        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $node[$key] = array_keys($value) === ['demo'] ? $importMedia->handle((string) $value['demo'])?->id : $this->withAssetIds($value, $importMedia);
        }

        return $node;
    }

    /**
     * The home page's construction-progress block: the ongoing projects with the most recent update.
     *
     * @return list<int>
     */
    private function progressProjects(): array
    {
        return Project::query()->published()->where('lifecycle', Lifecycle::Ongoing->value)->with('progressUpdates')->get()
            ->filter(fn (Project $project): bool => $project->progressUpdates->isNotEmpty())
            ->sortByDesc(fn (Project $project) => $project->progressUpdates->first()->reported_on)
            ->take(3)->pluck('id')->map(intval(...))->values()->all();
    }
}
