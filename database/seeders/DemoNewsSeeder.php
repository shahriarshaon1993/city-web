<?php

namespace Database\Seeders;

use App\Actions\Posts\SavePost;
use App\Actions\Publishing\PublishEntity;
use App\Enums\PublishStatus;
use App\Http\Resources\Media\MediaImageResource;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use Database\Seeders\Data\DemoNews;
use Database\Seeders\Support\ImportSeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The placeholder news for the client's review (outside production only): the posts of `DemoNews` as real
 * posts with library photos, tags and related projects, published on their dates. The post dated in the
 * future stays scheduled, which proves AC-NEWS-01. Runs only while there are no posts at all, so it never
 * touches what editors wrote; without the demo photos a post stays a draft. Removed with the demo
 * content in 16.4.
 */
class DemoNewsSeeder extends Seeder
{
    public function run(SavePost $savePost, PublishEntity $publish, ImportSeedMedia $importMedia): void
    {
        if (app()->isProduction() || Post::withTrashed()->exists()) {
            return;
        }

        $categories = PostCategory::query()->pluck('id', 'slug');
        $projects = Project::query()->pluck('id', 'slug');
        $tagNames = DemoNews::TAGS;

        // Oldest first, so the ids follow the dates.
        foreach (array_reverse(DemoNews::all()) as $source) {
            $post = DB::transaction(function () use ($savePost, $importMedia, $source, $categories, $projects, $tagNames): Post {
                $image = function (string $key) use ($importMedia): ?array {
                    $asset = $importMedia->handle($key);

                    if ($asset === null) {
                        return null;
                    }

                    $shape = (new MediaImageResource($asset))->resolve();

                    return [$asset->id, (string) $shape['alt'], (string) $shape['fallback'], (int) $shape['width'], (int) $shape['height']];
                };

                return $savePost->handle(null, [
                    'title' => $source['title'],
                    'slug' => $source['slug'],
                    'excerpt' => $source['excerpt'],
                    'body' => DemoNews::bodyHtml($source['body'], $image),
                    'cover_asset_id' => $importMedia->handle($source['cover'])?->id,
                    'category_id' => $categories[$source['category']],
                    'author_user_id' => null,
                    'is_featured' => $source['featured'],
                    'tags' => array_map(fn (string $slug): string => $tagNames[$slug], $source['tags']),
                    'project_ids' => array_values(array_filter(array_map(fn (string $slug): ?int => $projects[$slug] ?? null, $source['projects']))),
                    'seo' => null,
                ], null, false);
            });

            $this->publish($publish, $post, DemoNews::publishedAt($source['published_at']));
        }
    }

    private function publish(PublishEntity $publish, Post $post, Carbon $at): void
    {
        if ($at->isFuture()) {
            $post->forceFill(['publish_status' => PublishStatus::Scheduled, 'published_at' => $at])->saveQuietly();

            return;
        }

        try {
            $publish->handle(null, $post);
            $post->forceFill(['published_at' => $at])->saveQuietly();
        } catch (ValidationException $exception) {
            $this->command?->warn("Post “{$post->slug}” stays a draft: ".implode(' ', $exception->validator->errors()->all()));
        }
    }
}
