<?php

namespace App\Actions\Posts;

use App\Actions\Activity\LogActivity;
use App\Actions\News\ResolveTags;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\News\PostBody;
use Illuminate\Support\Facades\DB;

/**
 * FR-NEWS-001: creates or updates the live row of a post with its tags, related projects, SEO and media
 * usages in one transaction. The body is purified here (NFR-SEC-03) and the read time recomputed
 * (FR-NEWS-004). The payload is the editor form (`PostEditor::form`, validated by `SavePostRequest`).
 *
 * Like every entity, saving never publishes: a new post is a Draft, an update keeps its status, and an
 * edit of a published post goes to its pending revision one level up (`SaveEntityChanges`, CMS-PUB-002).
 */
final class SavePost
{
    public function __construct(
        private readonly ResolveTags $resolveTags,
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly SyncPostMediaUsages $syncPostMediaUsages,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of the post Form Request
     * @param  bool  $log  false for an autosave, for applying a revision and for seeding (the caller logs)
     */
    public function handle(?User $actor, array $data, ?Post $post = null, bool $log = true): Post
    {
        return DB::transaction(function () use ($actor, $data, $post, $log): Post {
            $creating = $post === null;
            $post ??= new Post(['publish_status' => PublishStatus::Draft]);

            $body = PostBody::clean($data['body'] ?? null);

            $post->fill([
                'title' => trim((string) $data['title']),
                'slug' => $data['slug'],
                'excerpt' => trim((string) ($data['excerpt'] ?? '')),
                'body' => $body === '' ? null : $body,
                'cover_asset_id' => ($data['cover_asset_id'] ?? null) ?: null,
                'category_id' => (int) $data['category_id'],
                'author_user_id' => ($data['author_user_id'] ?? null) ?: null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'read_minutes' => PostBody::readMinutes($body),
            ]);

            $changed = array_keys($post->getDirty());
            $post->bumpLock($actor);
            $post->save();

            $tags = array_map(fn (Tag $tag): int => $tag->id, $this->resolveTags->handle(array_slice(array_values($data['tags'] ?? []), 0, Post::TAGS_MAX)));
            $post->tags()->sync($tags);

            $projects = [];

            foreach (array_values(array_unique(array_map(intval(...), $data['project_ids'] ?? []))) as $position => $id) {
                $projects[$id] = ['sort_order' => $position];
            }

            $post->projects()->sync($projects);
            $this->saveSeoMeta->handle($post, $data['seo'] ?? null);
            $this->syncPostMediaUsages->handle($post);

            if ($log) {
                $this->logActivity->handle(
                    'posts',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Post created: ' : 'Post updated: ').$post->title,
                    $actor,
                    $post,
                    ['label' => $post->title, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at', 'lock_version', 'edited_by']))],
                );
            }

            return $post->unsetRelation('tags')->unsetRelation('projects');
        });
    }
}
