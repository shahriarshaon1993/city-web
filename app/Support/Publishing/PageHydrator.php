<?php

namespace App\Support\Publishing;

use App\Actions\Seo\SaveSeoMeta;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\SeoMeta;

/**
 * An unsaved copy of a page as an editor payload would make it (CMS-PUB-002): the live row with the
 * payload's title and date, its blocks as unsaved models in payload order, and its SEO set as loaded
 * relations. Nothing is written; the public renderer, the preview and the publish checklist read it
 * like a saved page. A block without an id gets a negative one.
 */
final class PageHydrator
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Page $live, array $payload): Page
    {
        $page = $live->replicate();
        $page->forceFill([
            'id' => $live->id, 'title' => $payload['title'] ?? $live->title, 'created_at' => $live->created_at, 'updated_at' => $live->updated_at,
            'last_reviewed_at' => ($payload['last_reviewed_at'] ?? null) ?: null,
        ]);

        if (! $live->is_system && isset($payload['slug'], $payload['template'])) {
            $page->forceFill(['slug' => $payload['slug'], 'template' => $payload['template']]);
        }

        $page->exists = true;

        $blocks = [];

        foreach (array_values($payload['blocks'] ?? []) as $position => $block) {
            $model = (new PageBlock)->forceFill([
                'id' => isset($block['id']) ? (int) $block['id'] : -($position + 1),
                'page_id' => $live->id,
                'type' => $block['type'],
                'data' => (array) ($block['data'] ?? []),
                'is_visible' => (bool) ($block['is_visible'] ?? true),
                'anchor_id' => ($block['anchor_id'] ?? '') === '' ? null : $block['anchor_id'],
                'background' => $block['background'] ?? 'default',
                'spacing' => $block['spacing'] ?? 'md',
                'animate' => (bool) ($block['animate'] ?? true),
                'sort_order' => $position,
            ]);
            $model->exists = true;
            $blocks[] = $model;
        }

        $seoValues = SaveSeoMeta::values($payload['seo'] ?? null);
        $og = $seoValues['og_image_asset_id'];
        $seo = SaveSeoMeta::isDefault($seoValues) ? null : (new SeoMeta)->forceFill($seoValues);
        $seo?->setRelation('ogImage', $og === null ? null : MediaAsset::query()->with('media')->find((int) $og));

        $page->setRelation('blocks', $page->newCollection($blocks));
        $page->setRelation('seoMeta', $seo);
        $page->syncOriginal();

        return $page;
    }
}
