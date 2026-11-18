<?php

namespace App\Actions\Pages;

use App\Actions\Activity\LogActivity;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use App\Support\Blocks\BlockSanitizer;
use App\Support\Blocks\BlockType;
use Illuminate\Support\Facades\DB;

/**
 * CMS-PB-001 / FR-CMS: creates or updates the live row of a page with its blocks, SEO and media usages
 * in one transaction. The payload is the editor form (`PageEditor::form`, validated by `PageRules`);
 * blocks that carry an `id` are updated in place, new ones are created, blocks missing from the payload
 * are deleted, and `sort_order` follows the payload. A block whose data fails its schema is stored all
 * the same (CMS-PB-003): it only blocks publishing.
 *
 * Like every entity, saving never publishes: a new page is a Draft, an update keeps its status, and an
 * edit of a published page goes to its pending revision one level up (`SaveEntityChanges`, CMS-PUB-002).
 * A system page keeps its slug and template.
 */
final class SavePage
{
    public function __construct(
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly SyncPageMediaUsages $syncPageMediaUsages,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of the page Form Request
     * @param  bool  $log  false for an autosave, for applying a revision and for seeding (the caller logs)
     */
    public function handle(?User $actor, array $data, ?Page $page = null, bool $log = true): Page
    {
        return DB::transaction(function () use ($actor, $data, $page, $log): Page {
            $creating = $page === null;
            $page ??= new Page(['publish_status' => PublishStatus::Draft]);

            $page->fill([
                'title' => $data['title'],
                'last_reviewed_at' => ($data['last_reviewed_at'] ?? null) ?: null,
            ]);

            if (! $page->is_system) {
                $page->fill(['slug' => $data['slug'], 'template' => $data['template'] ?? Page::BUILDER]);
            }

            $changed = array_keys($page->getDirty());
            $page->bumpLock($actor);
            $page->save();

            $this->syncBlocks($page, array_values($data['blocks'] ?? []));
            $this->saveSeoMeta->handle($page, $data['seo'] ?? null);
            $this->syncPageMediaUsages->handle($page);

            if ($log) {
                $this->logActivity->handle(
                    'pages',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Page created: ' : 'Page updated: ').$page->title,
                    $actor,
                    $page,
                    ['label' => $page->title, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at', 'lock_version', 'edited_by']))],
                );
            }

            return $page->unsetRelation('blocks');
        });
    }

    /**
     * @param  list<array<string, mixed>>  $payload
     */
    private function syncBlocks(Page $page, array $payload): void
    {
        $existing = $page->blocks()->get()->keyBy('id');
        $keep = array_values(array_filter(array_map(fn (array $block): ?int => isset($block['id']) && $existing->has((int) $block['id']) ? (int) $block['id'] : null, $payload)));

        $page->blocks()->whereNotIn('id', $keep)->delete();
        // Anchors are unique per page: free them first so two blocks can swap one.
        $page->blocks()->update(['anchor_id' => null]);

        foreach ($payload as $position => $block) {
            $type = BlockType::from((string) $block['type']);
            $attributes = [
                'type' => $type->value,
                'data' => BlockSanitizer::clean($type, (array) ($block['data'] ?? [])),
                'is_visible' => (bool) ($block['is_visible'] ?? true),
                'anchor_id' => ($block['anchor_id'] ?? '') === '' ? null : $block['anchor_id'],
                'background' => $block['background'] ?? 'default',
                'spacing' => $block['spacing'] ?? 'md',
                'animate' => (bool) ($block['animate'] ?? true),
                'sort_order' => $position,
            ];

            $model = isset($block['id']) ? $existing->get((int) $block['id']) : null;

            if ($model instanceof PageBlock) {
                $model->update($attributes);
            } else {
                $page->blocks()->create($attributes);
            }
        }
    }
}
