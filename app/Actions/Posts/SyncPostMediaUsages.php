<?php

namespace App\Actions\Posts;

use App\Actions\Media\SyncMediaUsages;
use App\Models\Post;
use App\Support\News\PostBody;

/**
 * DM-MUSE / CMS-MED-008: records the cover, every library image inside the body and the SEO share
 * image as usages of the post, so the library lists where each is used and refuses to delete it.
 * The post's save Action calls this.
 */
final class SyncPostMediaUsages
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    public function handle(Post $post): void
    {
        $this->syncMediaUsages->handle($post, 'cover', $post->cover_asset_id === null ? [] : [$post->cover_asset_id]);
        $this->syncMediaUsages->handle($post, 'body', PostBody::assetIds($post->body));

        $og = $post->seoMeta()->value('og_image_asset_id');
        $this->syncMediaUsages->handle($post, 'seo_og', $og === null ? [] : [(int) $og]);
    }
}
