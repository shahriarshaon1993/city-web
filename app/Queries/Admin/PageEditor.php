<?php

namespace App\Queries\Admin;

use App\Models\Page;
use App\Models\PageBlock;

/**
 * The editor payload of a page (CMS-PUB-002): the form in the shape of the page's Form Request, which
 * is also what a revision stores. `blocks` keep their ids so a save updates them in place.
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
}
