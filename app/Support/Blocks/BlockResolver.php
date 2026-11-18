<?php

namespace App\Support\Blocks;

use App\Models\Page;
use App\Models\PageBlock;
use Throwable;

/**
 * Stored blocks → the public `blocks` prop (DM-BLOCK shape of resources/js/types/blocks.ts) that
 * `BlockRenderer` draws. Hidden blocks are dropped (CMS-PB-001). A block that cannot be resolved is
 * reported and left out, so one broken block never takes the page down.
 */
final class BlockResolver
{
    /**
     * @param  iterable<PageBlock>  $blocks  in page order
     * @return list<array<string, mixed>>
     */
    public static function resolve(Page $page, iterable $blocks): array
    {
        $visible = array_values(array_filter(
            is_array($blocks) ? $blocks : iterator_to_array($blocks, false),
            fn (PageBlock $block): bool => $block->is_visible,
        ));

        $context = BlockContext::for($page, $visible);
        $resolved = [];

        foreach ($visible as $block) {
            $type = $block->blockType();

            if ($type === null) {
                continue;
            }

            try {
                $data = $type->schema()->resolve($block->data, $context);
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }

            $resolved[] = [
                'id' => (int) $block->id,
                'type' => $type->value,
                'is_visible' => true,
                'anchor_id' => $block->anchor_id,
                'background' => $block->background,
                'spacing' => $block->spacing,
                'animate' => $block->animate,
                'data' => $data,
            ];
        }

        return $resolved;
    }
}
