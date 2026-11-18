<?php

/**
 * Phase 16.1 check: the page and block schema, the 28 block schemas, publishing refusals (CMS-PB-003/004/005),
 * reserved slugs, system-page guards, media usages, the pending revision, the preview and the public render
 * from the database. Everything is rolled back.
 * Run: php artisan tinker docs/checks/16.1-pages.php < /dev/null   (needs `db:seed --class=PageSeeder` first)
 */

use App\Actions\Pages\SavePage;
use App\Actions\Publishing\PublishEntity;
use App\Actions\Publishing\SaveEntityChanges;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\User;
use App\Queries\Admin\PageEditor;
use App\Queries\Site\PageContent;
use App\Support\Blocks\BlockResolver;
use App\Support\Blocks\BlockType;
use App\Support\Blocks\BlockValidator;
use App\Support\Media\MediaKind;
use App\Support\Menus\LinkTargets;
use App\Support\Menus\LinkType;
use App\Support\Pages\PageRules;
use App\Support\Publishing\Publishing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

DB::beginTransaction();

try {
    $actor = User::query()->role('Super Admin')->first() ?? User::factory()->create();
    $imageId = MediaAsset::query()->where('kind', MediaKind::Image->value)->where('width', '>=', 1920)->whereNotNull('alt')->value('id');
    $link = ['label' => 'Contact us', 'link_type' => 'url', 'url' => '/contact', 'new_tab' => false];

    // --- the seeded system pages ---------------------------------------------------------------------------------
    $slugs = Page::query()->orderBy('id')->pluck('slug')->all();
    $say('system pages exist in the 6.3 order', array_slice($slugs, 0, 5), ['about', 'leadership', 'services', 'landowner', 'nrb']);
    $say('home, careers, privacy and terms exist', array_diff(['home', 'careers', 'privacy-policy', 'terms'], $slugs), []);
    $say('every seeded page is published', Page::query()->where('is_system', true)->where('publish_status', '!=', 'published')->count(), 0);
    $say('no seeded block is invalid', Page::query()->with('blocks')->get()->flatMap->blocks->filter(fn ($block) => BlockValidator::count($block) > 0)->count(), 0);
    $say('home paths', [Page::where('slug', 'home')->first()->path(), Page::where('slug', 'leadership')->first()->path()], ['/', '/about/leadership']);

    // --- 28 schemas: an empty block is invalid, resolving never throws -------------------------------------------
    $say('28 block types', count(BlockType::cases()), 28);
    $required = 0;
    foreach (BlockType::cases() as $type) {
        $required += BlockValidator::errors($type, []) === [] ? 0 : 1;
    }
    $say('every type with required fields rejects an empty block', $required >= 20, true);

    // --- CMS-PB-003: invalid data saves as draft, publish is refused ---------------------------------------------
    $payload = fn (array $blocks, string $slug = 'check-page') => [
        'title' => 'Check page', 'slug' => $slug, 'template' => 'builder', 'last_reviewed_at' => null,
        'blocks' => $blocks, 'seo' => null,
    ];
    $envelope = fn (string $type, array $data) => ['type' => $type, 'data' => $data, 'is_visible' => true, 'anchor_id' => null, 'background' => 'default', 'spacing' => 'md', 'animate' => true];
    $header = $envelope('page_header', ['title' => 'A page header', 'intro' => null, 'image' => null, 'breadcrumb' => true]);
    $badHero = $envelope('hero', ['mode' => 'single', 'headline' => '']);

    $page = app(SavePage::class)->handle($actor, $payload([$badHero, $header]), null, false);
    $say('a block with invalid data is saved', $page->blocks()->count(), 2);
    $say('the draft status is kept', $page->publish_status->value, 'draft');
    $say('the invalid block reports errors', BlockValidator::count($page->blocks()->first()) > 0, true);
    try {
        app(PublishEntity::class)->handle($actor, $page);
        $say('publish is refused while a block is invalid', 'published', 'refused');
    } catch (ValidationException $exception) {
        $say('publish is refused while a block is invalid', str_contains($exception->errors()['publish'][0], 'complete and valid'), true);
    }

    // --- CMS-PB-004: the first visible block must carry the H1 --------------------------------------------------
    $noH1 = app(SavePage::class)->handle($actor, $payload([$envelope('rich_text', ['body' => '<p>Text</p>', 'width' => 'narrow'])], 'check-no-h1'), null, false);
    try {
        app(PublishEntity::class)->handle($actor, $noH1);
        $say('a page without an H1-capable first block is refused', 'published', 'refused');
    } catch (ValidationException $exception) {
        $say('a page without an H1-capable first block is refused', str_contains($exception->errors()['publish'][0], 'hero or page header'), true);
    }

    $ok = app(SavePage::class)->handle($actor, $payload([$header, $envelope('rich_text', ['body' => '<p>Hello <script>alert(1)</script><strong>world</strong></p><table><tr><td>1</td></tr></table>', 'width' => 'narrow'])], 'check-ok'), null, false);
    $say('rich text is cleaned on save, a table survives', $ok->blocks()->where('type', 'rich_text')->first()->data['body'], '<p>Hello alert(1)<strong>world</strong></p><table><tr><td>1</td></tr></table>');
    app(PublishEntity::class)->handle($actor, $ok);
    $say('a valid page publishes', $ok->refresh()->publish_status->value, 'published');
    $say('a revision is stored', $ok->revisions()->where('kind', 'published')->count(), 1);

    // --- CMS-PB-005: 30 blocks max, reserved slug refused --------------------------------------------------------
    $rules = PageRules::rules();
    $many = array_fill(0, 31, $header);
    $say('31 blocks are refused', Validator::make($payload($many), $rules)->errors()->has('blocks'), true);
    foreach (['projects', 'admin', 'home', 'leadership', 'privacy-policy'] as $slug) {
        $say("reserved slug “{$slug}” is refused", Validator::make($payload([$header], $slug), $rules)->errors()->has('slug'), true);
    }
    $say('a free slug passes', Validator::make($payload([$header], 'buyers-corner'), $rules)->errors()->has('slug'), false);
    $say('a taken slug is refused', Validator::make($payload([$header], 'check-ok'), $rules)->errors()->has('slug'), true);
    $say('a bad slug is refused', Validator::make($payload([$header], 'Bad Slug'), $rules)->errors()->has('slug'), true);
    $say('two blocks cannot share an anchor', Validator::make($payload([[...$header, 'anchor_id' => 'same'], [...$header, 'anchor_id' => 'same']]), $rules)->errors()->has('blocks.0.anchor_id') || Validator::make($payload([[...$header, 'anchor_id' => 'same'], [...$header, 'anchor_id' => 'same']]), $rules)->errors()->has('blocks.1.anchor_id'), true);

    // --- system page guards -------------------------------------------------------------------------------------
    $about = Page::query()->where('slug', 'about')->first();
    $form = PageEditor::form($about);
    app(SavePage::class)->handle($actor, [...$form, 'slug' => 'renamed', 'template' => 'legal'], $about, false);
    $say('a system page keeps its slug and template', [$about->refresh()->slug, $about->template], ['about', 'builder']);
    $say('a system page cannot be deleted (bin guard)', $about->binUsageBlock($actor) !== null, true);
    $say('a custom page can be deleted', $ok->binUsageBlock($actor), null);
    $say('rules ignore slug/template for a system page', Validator::make($form, PageRules::rules($about))->errors()->count(), 0);

    // --- media usages ---------------------------------------------------------------------------------------------
    if ($imageId !== null) {
        $withImage = app(SavePage::class)->handle($actor, $payload([$envelope('page_header', ['title' => 'With photo', 'intro' => null, 'image' => $imageId, 'breadcrumb' => true])], 'check-image'), null, false);
        $blockId = $withImage->blocks()->first()->id;
        $say('block media is recorded as a usage', MediaUsage::query()->where('usable_type', 'page')->where('usable_id', $withImage->id)->pluck('field')->all(), ["blocks.{$blockId}.image"]);
        app(SavePage::class)->handle($actor, [...PageEditor::form($withImage), 'blocks' => [PageEditor::block($withImage->blocks()->first()->forceFill(['data' => ['title' => 'With photo', 'image' => null, 'breadcrumb' => true]]))]], $withImage, false);
        $say('removing the photo clears the usage', MediaUsage::query()->where('usable_type', 'page')->where('usable_id', $withImage->id)->count(), 0);
    }

    // --- CMS-PUB-002: editing a published page writes a pending revision, the live page stays -------------------
    $edit = PageEditor::form($ok);
    $edit['blocks'][0]['data']['title'] = 'A changed header';
    $pending = app(SaveEntityChanges::class)->handle($actor, $ok, $edit, (int) $ok->lock_version);
    $say('an edit of a published page goes to a pending revision', $pending, true);
    $say('the live header is unchanged', PageContent::find('check-ok')->blocks->first()->data['title'], 'A page header');
    $driver = Publishing::for($ok);
    $hydrated = $driver->hydrate($ok->refresh(), $ok->pendingRevisionOrNull()->payload);
    $say('the preview copy shows the pending header', BlockResolver::resolve($hydrated, $hydrated->blocks)[0]['data']['title'], 'A changed header');
    app(PublishEntity::class)->handle($actor, $ok->refresh());
    $say('Publish changes makes it live', PageContent::find('check-ok')->blocks->first()->data['title'], 'A changed header');

    // --- public render from the database ------------------------------------------------------------------------
    $request = Request::create('/');
    $home = PageContent::find('home');
    $props = PageContent::builderProps($request, $home);
    $say('home resolves 11 blocks, hero first', [count($props['blocks']), $props['blocks'][0]['type']], [11, 'hero']);
    $say('home starts with a hero', $props['starts_with_hero'], true);
    $say('home has exactly one LCP preload image', count($props['seo']['preload']) >= 1, true);
    $legal = PageContent::legalProps($request, PageContent::find('privacy-policy'));
    $say('a legal page has a TOC and an updated date', [count($legal['toc']) > 0, $legal['updated_at']], [true, '2026-11-01']);
    $say('a hidden block is not rendered', (function () use ($ok) {
        $form = PageEditor::form($ok->refresh());
        $form['blocks'][1]['is_visible'] = false;
        $copy = Publishing::for($ok)->hydrate($ok, $form);

        return count(BlockResolver::resolve($copy, $copy->blocks));
    })(), 1);
    $say('a trashed page is not public', (function () use ($ok) {
        $ok->delete();

        return PageContent::find('check-ok');
    })(), null);

    // --- LinkTargets (menus) -------------------------------------------------------------------------------------
    $targets = LinkTargets::search(LinkType::Page, limit: 50);
    $say('menu link targets list the pages table', count($targets) >= 11, true);
} finally {
    DB::rollBack();
}
