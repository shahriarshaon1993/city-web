<?php

namespace App\Actions\Pages;

use App\Actions\Media\SyncMediaUsages;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Support\Blocks\MediaPaths;

/**
 * DM-MUSE / CMS-PB: every library asset inside a page's block data is recorded as a usage of the
 * page with the field `blocks.{block id}.{path}`, so the library lists where it is used and refuses
 * to delete it (CMS-MED-007/008). Also the SEO share image. The page's save Action calls this.
 */
final class SyncPageMediaUsages
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    public function handle(Page $page): void
    {
        MediaUsage::query()
            ->where('usable_type', $page->getMorphClass())
            ->where('usable_id', $page->getKey())
            ->where('field', 'like', 'blocks.%')
            ->delete();

        $position = 0;

        foreach ($page->blocks()->get() as $block) {
            $type = $block->blockType();

            if ($type === null) {
                continue;
            }

            foreach (MediaPaths::entries($type, $block->data) as $entry) {
                MediaUsage::query()->firstOrCreate(
                    ['media_asset_id' => $entry['id'], 'usable_type' => $page->getMorphClass(), 'usable_id' => $page->getKey(), 'field' => "blocks.{$block->id}.{$entry['path']}"],
                    ['position' => $position++],
                );
            }
        }

        $og = $page->seoMeta()->value('og_image_asset_id');
        $this->syncMediaUsages->handle($page, 'seo_og', $og === null ? [] : [(int) $og]);
    }
}
