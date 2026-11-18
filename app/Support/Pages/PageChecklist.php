<?php

namespace App\Support\Pages;

use App\Models\Page;
use App\Models\PageBlock;
use App\Support\Blocks\BlockValidator;

/**
 * CMS-PB-003/004/005 + VAL-05: the publish-only rules of a page, each with the editor tab and field that
 * fixes it (CMP-ADM-CHECKLIST "Fix" links). Saving a draft is never blocked by these; Publish and
 * Schedule are. It reads a saved or hydrated page.
 *
 * @phpstan-type Item array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}
 */
final class PageChecklist
{
    /**
     * @return list<Item>
     */
    public function items(Page $page): array
    {
        $page->loadMissing('blocks');
        $blocks = $page->blocks;
        $visible = $blocks->filter(fn (PageBlock $block): bool => $block->is_visible)->values();
        $invalid = $blocks->filter(fn (PageBlock $block): bool => BlockValidator::count($block) > 0)->count();

        $items = [
            $page->isLegal()
                ? $this->item('text', 'The page has its text', $this->hasLegalText(array_values($visible->all())), null, 'blocks', 'blocks')
                : $this->item('h1', 'The first visible block is a hero or page header with a heading', $this->opensWithHeading($visible->first()), null, 'blocks', 'blocks'),
            $this->item('valid', 'Every block is complete and valid', $invalid === 0, $invalid === 0 ? null : $invalid.' with errors', 'blocks', 'blocks'),
            $this->item('limit', 'No more than '.Page::BLOCKS_MAX.' blocks', $blocks->count() <= Page::BLOCKS_MAX, $blocks->count().' of '.Page::BLOCKS_MAX, 'blocks', 'blocks'),
        ];

        return $items;
    }

    /** CMS-PB-004: its heading becomes the page H1. */
    private function opensWithHeading(?PageBlock $first): bool
    {
        $type = $first?->blockType();

        return $type !== null && $type->canOpenPage() && trim((string) $type->schema()->headline($first->data)) !== '';
    }

    /**
     * @param  list<PageBlock>  $visible
     */
    private function hasLegalText(array $visible): bool
    {
        return array_any($visible, fn (PageBlock $block): bool => $block->type === 'rich_text' && trim(strip_tags((string) ($block->data['body'] ?? ''))) !== '');
    }

    /**
     * @return Item
     */
    private function item(string $key, string $label, bool $passed, ?string $detail, string $tab, string $field): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => $passed, 'detail' => $detail, 'tab' => $tab, 'field' => $field];
    }
}
