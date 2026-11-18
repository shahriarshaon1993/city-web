<?php

/**
 * Phase 16.3 check: posts, categories and tags (FR-NEWS-001…008), the body rules (heading order, alt text),
 * purified HTML (NFR-SEC-03), AC-NEWS-01 (a scheduled post is a 404), the publish checklist, tag merge, the
 * category delete guard and the RSS feed. Everything is rolled back.
 * Run: php docs/checks/16.3-news.php   (needs `db:seed --class=NewsCategorySeeder` first)
 */

use App\Actions\News\DeletePostCategory;
use App\Actions\News\MergeTags;
use App\Actions\News\RenameTag;
use App\Actions\Posts\SavePost;
use App\Actions\Publishing\PublishEntity;
use App\Enums\PublishStatus;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use App\Queries\Site\NewsQuery;
use App\Support\Media\MediaKind;
use App\Support\News\PostBody;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

if (! function_exists('app')) {
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
}

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

DB::beginTransaction();

try {
    $actor = User::query()->role('Super Admin')->first() ?? User::factory()->create();
    $category = PostCategory::query()->orderBy('sort_order')->firstOrFail();
    $other = PostCategory::query()->whereKeyNot($category->id)->orderBy('sort_order')->firstOrFail();
    $image = MediaAsset::query()->where('kind', MediaKind::Image->value)->where('width', '>=', 1600)->whereNotNull('alt')->firstOrFail();
    $save = app(SavePost::class);

    $payload = fn (array $over = []): array => [
        'title' => 'Check post', 'slug' => 'check-post-163', 'excerpt' => 'A summary.', 'body' => '<h2>One</h2><p>Text.</p>',
        'cover_asset_id' => $image->id, 'category_id' => $category->id, 'author_user_id' => null, 'is_featured' => false,
        'tags' => ['NRB', 'nrb', 'Check Tag'], 'project_ids' => [], 'seo' => null, ...$over,
    ];

    // --- body rules (FR-NEWS-005, AC-NEWS-02) ---------------------------------------------------
    $say('H4 right after H2 is refused', PostBody::problems('<h2>A</h2><h4>B</h4>'), ['Heading 4 “B” needs a Heading 3 before it.']);
    $say('body starts at H2', count(PostBody::problems('<h3>A</h3>')), 1);
    $say('image without alt (AC-NEWS-02)', PostBody::problems('<p>x</p><img src="/a.jpg" alt="ok"><img src="/b.jpg" alt="">'), ['Image 2 needs alt text or must be marked decorative.']);
    $say('decorative image passes', PostBody::problems('<img src="/b.jpg" alt="" data-decorative="true">'), []);

    $request = new SavePostRequest;
    $request->setUserResolver(fn () => $actor);
    $rules = $request->rules();
    $bad = Validator::make($payload(['body' => '<h2>A</h2><h4>B</h4>', 'slug' => 'check-bad-163']), $rules);
    $say('Form Request refuses the heading order', $bad->fails() && $bad->errors()->has('body'), true);
    $good = Validator::make($payload(['slug' => 'check-good-163']), $rules);
    $say('Form Request accepts a valid post', $good->passes(), true);

    // --- purifier (NFR-SEC-03) ------------------------------------------------------------------
    $clean = PostBody::clean('<p>Hi<script>alert(1)</script><a href="javascript:alert(1)" onclick="x()">x</a></p><iframe src="https://evil.example/x"></iframe>');
    $say('script, javascript: links, handlers and foreign frames are gone', [str_contains($clean, 'script'), str_contains($clean, 'javascript'), str_contains($clean, 'onclick'), str_contains($clean, 'iframe')], [false, false, false, false]);
    $say('an empty editor stores nothing', PostBody::clean('<p></p>'), '');

    // --- save, tags, read time ------------------------------------------------------------------
    $post = $save->handle($actor, $payload());
    $say('new post is a draft', $post->publish_status, PublishStatus::Draft);
    $say('tags NRB / nrb are one', $post->tags()->pluck('name')->sort()->values()->all(), ['Check Tag', 'NRB']);
    $say('read time is at least 1', $post->read_minutes, 1);
    $say('cover usage recorded', $post->cover_asset_id === $image->id && DB::table('media_usages')->where('usable_type', 'post')->where('usable_id', $post->id)->where('field', 'cover')->exists(), true);

    // --- checklist, publish, AC-NEWS-01 ---------------------------------------------------------
    $published = app(PublishEntity::class)->handle($actor, $post);
    $say('post with cover, summary and text publishes', $published->publish_status, PublishStatus::Published);
    $say('published post is public', NewsQuery::find('check-post-163')?->id, $post->id);

    $post->forceFill(['publish_status' => PublishStatus::Scheduled, 'published_at' => now()->addDays(3)])->saveQuietly();
    $say('scheduled post is not public (404)', NewsQuery::find('check-post-163'), null);

    $draft = $save->handle($actor, $payload(['slug' => 'check-draft-163', 'cover_asset_id' => null, 'excerpt' => '']));
    try {
        app(PublishEntity::class)->handle($actor, $draft);
        $say('publish without cover and summary is refused', 'published', 'refused');
    } catch (ValidationException) {
        $say('publish without cover and summary is refused', 'refused', 'refused');
    }

    // --- tags: rename, merge ----------------------------------------------------------------------
    $nrb = Tag::query()->where('name', 'NRB')->firstOrFail();
    $extra = Tag::query()->where('name', 'Check Tag')->firstOrFail();
    app(RenameTag::class)->handle($actor, $extra, 'Check Renamed');
    $say('rename updates the slug', $extra->fresh()->slug, 'check-renamed');
    $moved = app(MergeTags::class)->handle($actor, $nrb, [$extra->id]);
    $say('merge re-tags once and deletes the merged tag', [Tag::query()->whereKey($extra->id)->exists(), $moved], [false, 0]);

    // --- category delete guard ----------------------------------------------------------------------
    $say('category with posts is blocked', $category->binUsageBlock($actor) !== null, true);
    app(DeletePostCategory::class)->handle($actor, $category, $other);
    $say('posts moved to the other category', $post->fresh()->category_id, $other->id);
    $say('category is in the bin', PostCategory::onlyTrashed()->whereKey($category->id)->exists(), true);

    // --- RSS ----------------------------------------------------------------------------------------
    $feed = app(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::create('/news/feed.xml'));
    $say('feed is RSS 2.0', [$feed->getStatusCode(), str_contains($feed->getContent(), '<rss version="2.0"'), substr_count($feed->getContent(), '<item>') <= 20], [200, true, true]);
} finally {
    DB::rollBack();
}
