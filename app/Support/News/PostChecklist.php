<?php

namespace App\Support\News;

use App\Models\Post;

/**
 * FR-NEWS-001/005/006 + VAL-05: the publish-only rules of a post, each with the editor tab and field that
 * fixes it (CMP-ADM-CHECKLIST "Fix" links). Saving a draft is never blocked by these; Publish and
 * Schedule are. It reads a saved or hydrated post.
 *
 * @phpstan-type Item array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}
 */
final class PostChecklist
{
    /**
     * @return list<Item>
     */
    public function items(Post $post): array
    {
        $post->loadMissing('cover.media');
        $cover = $post->cover;
        $problems = PostBody::problems($post->body);
        $words = PostBody::wordCount($post->body);

        return [
            $this->item('cover', 'A cover image', $cover !== null, null, 'content', 'field-cover_asset_id'),
            $this->item('cover_width', 'The cover is at least '.Post::COVER_MIN_WIDTH.' px wide', $cover === null || (int) $cover->width >= Post::COVER_MIN_WIDTH, $cover === null ? null : $cover->width.' px', 'content', 'field-cover_asset_id'),
            $this->item('cover_alt', 'The cover has alt text', $cover === null || $cover->is_decorative || trim((string) $cover->alt) !== '', null, 'content', 'field-cover_asset_id'),
            $this->item('excerpt', 'A short summary (excerpt)', trim($post->excerpt) !== '', null, 'content', 'field-excerpt'),
            $this->item('body', 'The article has text', $words > 0, $words === 0 ? null : $words.' words', 'content', 'field-body'),
            $this->item('body_valid', 'Headings are in order and every image has alt text', $problems === [], $problems[0] ?? null, 'content', 'field-body'),
        ];
    }

    /**
     * @return Item
     */
    private function item(string $key, string $label, bool $passed, ?string $detail, string $tab, string $field): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => $passed, 'detail' => $detail, 'tab' => $tab, 'field' => $field];
    }
}
