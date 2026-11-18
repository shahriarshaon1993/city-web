<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Support\News\PostBody;
use Illuminate\Http\Request;

/**
 * The editor payload of a post (CMS-PUB-002, ADM-SCR-08): the form in the shape of `SavePostRequest`,
 * which is also what a revision stores. Tags travel as names; related projects as ids in order.
 */
final class PostEditor
{
    /**
     * @return array<string, mixed>
     */
    public static function form(Post $post): array
    {
        $post->loadMissing(['tags', 'projects', 'seoMeta']);

        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => (string) $post->body,
            'cover_asset_id' => $post->cover_asset_id,
            'category_id' => $post->category_id,
            'author_user_id' => $post->author_user_id,
            'is_featured' => $post->is_featured,
            'tags' => $post->tags->pluck('name')->values()->all(),
            'project_ids' => $post->projects->pluck('id')->values()->all(),
            'seo' => ProjectEditor::seo($post->seoMeta),
        ];
    }

    /**
     * The form of a post that does not exist yet (CRUD-03: sensible defaults, a draft). The author is
     * the person writing it; the category is the first one.
     *
     * @return array<string, mixed>
     */
    public static function blank(User $actor): array
    {
        return [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'body' => '',
            'cover_asset_id' => null,
            'category_id' => PostCategory::query()->orderBy('sort_order')->orderBy('id')->value('id'),
            'author_user_id' => $actor->id,
            'is_featured' => false,
            'tags' => [],
            'project_ids' => [],
            'seo' => ProjectEditor::seo(null),
        ];
    }

    /**
     * The library items a form points at (cover, body images, share image), by id, for the field previews
     * and the image nodes of the editor.
     *
     * @param  array<string, mixed>  $form
     * @return array<int, array<string, mixed>>
     */
    public static function assets(Request $request, array $form): array
    {
        $ids = [$form['cover_asset_id'] ?? null, $form['seo']['og_image_asset_id'] ?? null, ...PostBody::assetIds($form['body'] ?? '')];
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->whereKey($ids)->with('media')->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        return [
            'categories' => PostCategory::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])
                ->map(fn (PostCategory $category): array => ['value' => $category->id, 'label' => $category->name])->all(),
            'authors' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])->all(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Project $project): array => ['value' => $project->id, 'label' => $project->name])->all(),
            'tag_suggestions' => Tag::query()->orderBy('name')->limit(500)->pluck('name')->all(),
            'limits' => [
                'title' => Post::TITLE_MAX,
                'excerpt' => Post::EXCERPT_MAX,
                'tags' => Post::TAGS_MAX,
                'projects' => Post::PROJECTS_MAX,
                'cover_min_width' => Post::COVER_MIN_WIDTH,
            ],
        ];
    }
}
