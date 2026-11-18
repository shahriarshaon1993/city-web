<?php

namespace App\Support\Publishing;

use App\Actions\Seo\SaveSeoMeta;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Models\User;
use App\Support\News\PostBody;
use Illuminate\Support\Str;

/**
 * An unsaved copy of a post as an editor payload would make it (CMS-PUB-002): the live row with the
 * payload's columns, and its category, cover, author, tags, related projects and SEO set as loaded
 * relations. Nothing is written; mirrors `SavePost`. The public template, the preview and the publish
 * checklist read it like a saved post.
 */
final class PostHydrator
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Post $live, array $payload): Post
    {
        $body = PostBody::clean($payload['body'] ?? null);

        $post = $live->replicate();
        $post->forceFill([
            'id' => $live->id,
            'title' => trim((string) ($payload['title'] ?? $live->title)),
            'slug' => $payload['slug'] ?? $live->slug,
            'excerpt' => trim((string) ($payload['excerpt'] ?? '')),
            'body' => $body === '' ? null : $body,
            'cover_asset_id' => ($payload['cover_asset_id'] ?? null) ?: null,
            'category_id' => (int) ($payload['category_id'] ?? $live->category_id),
            'author_user_id' => ($payload['author_user_id'] ?? null) ?: null,
            'is_featured' => (bool) ($payload['is_featured'] ?? false),
            'read_minutes' => PostBody::readMinutes($body),
            'created_at' => $live->created_at,
            'updated_at' => $live->updated_at,
        ]);
        $post->exists = true;

        $seoValues = SaveSeoMeta::values($payload['seo'] ?? null);
        $assetIds = array_filter([$post->cover_asset_id, $seoValues['og_image_asset_id']]);
        $assets = $assetIds === [] ? collect() : MediaAsset::query()->whereKey($assetIds)->with('media')->get()->keyBy('id');

        $seo = SaveSeoMeta::isDefault($seoValues) ? null : (new SeoMeta)->forceFill($seoValues);
        $seo?->setRelation('ogImage', $assets->get((int) ($seoValues['og_image_asset_id'] ?? 0)));

        $tags = collect(array_slice(array_values($payload['tags'] ?? []), 0, Post::TAGS_MAX))
            ->map(fn (mixed $name): string => trim((string) $name))->filter()->unique(fn (string $name): string => mb_strtolower($name))
            ->map(fn (string $name): Tag => Tag::query()->where('name', $name)->first() ?? (new Tag)->forceFill(['name' => $name, 'slug' => Str::slug($name)]))
            ->values();

        $projectIds = array_values(array_unique(array_map(intval(...), $payload['project_ids'] ?? [])));
        $projects = Project::query()->whereKey($projectIds)->get()
            ->sortBy(fn (Project $project): int|false => array_search($project->id, $projectIds, true))->values();

        $post->setRelation('category', PostCategory::withTrashed()->find($post->category_id));
        $post->setRelation('cover', $assets->get((int) $post->cover_asset_id));
        $post->setRelation('author', $post->author_user_id === null ? null : User::withTrashed()->find($post->author_user_id));
        $post->setRelation('tags', $tags);
        $post->setRelation('projects', $projects);
        $post->setRelation('seoMeta', $seo);
        $post->syncOriginal();

        return $post;
    }
}
