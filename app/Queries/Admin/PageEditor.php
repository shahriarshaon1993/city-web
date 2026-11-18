<?php

namespace App\Queries\Admin;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Project;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockType;
use App\Support\Blocks\MediaPaths;
use App\Support\Blocks\Schemas\FeatureCardsSchema;
use App\Support\Blocks\Schemas\PartnersMarqueeSchema;
use App\Support\Demo\DemoNews;
use Illuminate\Http\Request;

/**
 * The editor payload of a page (CMS-PUB-002): the form in the shape of the page's Form Request, which
 * is also what a revision stores. `blocks` keep their ids so a save updates them in place. The option
 * lists and the asset map are what the builder's block forms need to render (ADM-SCR-06).
 */
final class PageEditor
{
    /**
     * @return array<string, mixed>
     */
    public static function form(Page $page): array
    {
        $page->loadMissing(['blocks', 'seoMeta']);

        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template,
            'last_reviewed_at' => $page->last_reviewed_at?->toDateString(),
            'blocks' => $page->blocks->map(fn (PageBlock $block): array => self::block($block))->values()->all(),
            'seo' => ProjectEditor::seo($page->seoMeta),
        ];
    }

    /**
     * The form of a page that does not exist yet (CRUD-03: sensible defaults, a draft).
     *
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'title' => '',
            'slug' => '',
            'template' => Page::BUILDER,
            'last_reviewed_at' => null,
            'blocks' => [],
            'seo' => ProjectEditor::seo(null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function block(PageBlock $block): array
    {
        return [
            'id' => $block->id > 0 ? $block->id : null,
            'type' => $block->type,
            'data' => $block->data,
            'is_visible' => $block->is_visible,
            'anchor_id' => $block->anchor_id,
            'background' => $block->background,
            'spacing' => $block->spacing,
            'animate' => $block->animate,
        ];
    }

    /**
     * What the editor needs on top of the stored form: every stored link (a `link_type` array) gets
     * the state of its target (`link`), which the link picker shows. The client strips it again
     * before saving, and the Form Request does too (`StoredLinks`).
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function decorate(array $form): array
    {
        $form['blocks'] = array_map(function (array $block): array {
            $block['data'] = self::decorateLinks((array) ($block['data'] ?? []));

            return $block;
        }, $form['blocks'] ?? []);

        return $form;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function decorateLinks(array $node): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = self::decorateLinks($value);
            }
        }

        if (isset($node['link_type']) && is_string($node['link_type'])) {
            $node['link'] = MenuEditor::link($node);
        }

        return $node;
    }

    /**
     * The library items a form points at (blocks and the share image), by id, for the field previews.
     *
     * @param  array<string, mixed>  $form
     * @return array<int, array<string, mixed>>
     */
    public static function assets(Request $request, array $form): array
    {
        $ids = [];

        foreach ($form['blocks'] ?? [] as $block) {
            $type = BlockType::tryFrom((string) ($block['type'] ?? ''));

            if ($type !== null) {
                array_push($ids, ...MediaPaths::ids($type, (array) ($block['data'] ?? [])));
            }
        }

        $ids[] = $form['seo']['og_image_asset_id'] ?? null;
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->whereKey($ids)->with('media')->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }

    /**
     * The option lists of the block forms (selects, pickers). Testimonials, FAQs and offices still come
     * from the demo content until 16.4 moves them to tables (BlockData is the only seam).
     *
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        $labels = fn (string $enum): array => array_map(fn ($case): array => ['value' => $case->value, 'label' => $case->label()], $enum::cases());
        $areas = Location::query()->areas()->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id']);

        return [
            'segments' => $labels(Segment::class),
            'lifecycles' => $labels(Lifecycle::class),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project): array => ['value' => $project->id, 'label' => $project->name])->all(),
            'areas' => $areas->map(fn (Location $area): array => ['value' => $area->id, 'label' => $area->name, 'group' => $area->parent?->name])->all(),
            'cities' => Location::query()->cities()->orderBy('name')->get(['slug', 'name'])
                ->map(fn (Location $city): array => ['value' => $city->slug, 'label' => $city->name])->all(),
            'testimonials' => array_map(fn (array $item): array => ['value' => $item['id'], 'label' => $item['person_name'].($item['designation'] ? ', '.$item['designation'] : '')], BlockData::testimonials()),
            'faq_categories' => array_map(fn (string $slug): array => ['value' => $slug, 'label' => ucfirst(str_replace(['-', '_'], ' ', $slug))], BlockData::faqCategories()),
            'faqs' => array_map(fn (array $item): array => ['value' => $item['id'], 'label' => $item['question']], BlockData::faqs(null)),
            'news_categories' => array_map(fn (array $item): array => ['value' => $item['slug'], 'label' => $item['name']], DemoNews::categories()),
            'offices' => array_map(fn (array $item): array => ['value' => $item['id'], 'label' => $item['name']], BlockData::offices()),
            'partner_categories' => array_map(fn (string $slug): array => ['value' => $slug, 'label' => ucfirst(str_replace('_', ' ', $slug))], PartnersMarqueeSchema::CATEGORIES),
            'block_icons' => FeatureCardsSchema::ICONS,
            ...MenuEditor::options(),
            'block_limit' => Page::BLOCKS_MAX,
        ];
    }
}
