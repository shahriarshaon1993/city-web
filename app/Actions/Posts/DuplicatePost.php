<?php

namespace App\Actions\Posts;

use App\Actions\Activity\LogActivity;
use App\Models\Post;
use App\Models\User;
use App\Queries\Admin\PostEditor;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-05: Duplicate post = a Draft "Copy of {title}" with a free slug (`-copy`, `-copy-2`…) that keeps the
 * body, cover, category, tags, related projects and the SEO panel. Revisions, slug history, the
 * featured flag and the published state are not copied.
 */
final class DuplicatePost
{
    public function __construct(
        private readonly SavePost $savePost,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, Post $source): Post
    {
        return DB::transaction(function () use ($actor, $source): Post {
            $form = PostEditor::form($source);
            $form['title'] = mb_substr("Copy of {$source->title}", 0, Post::TITLE_MAX);
            $form['slug'] = $this->freeSlug($source->slug);
            $form['is_featured'] = false;

            $copy = $this->savePost->handle($actor, $form, null, false);

            $this->logActivity->handle('posts', 'created', "Post duplicated: {$copy->title}", $actor, $copy, ['label' => $copy->title, 'source' => $source->title]);

            return $copy;
        });
    }

    /** Free among all posts, the bin included (DEL-08). */
    private function freeSlug(string $slug): string
    {
        $base = mb_substr($slug, 0, Post::TITLE_MAX - 9);
        $candidate = "{$base}-copy";

        for ($number = 2; Post::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-copy-{$number}";
        }

        return $candidate;
    }
}
