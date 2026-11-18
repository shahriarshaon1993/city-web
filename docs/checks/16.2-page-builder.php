<?php

/**
 * Phase 16.2 check: the page builder admin — the pages list, the editor payload, the live preview endpoint
 * (resolved blocks, per-block errors, checklist), saving (client state stripped, order kept, reserved slug
 * refused), duplicate (custom pages only), delete → Recycle bin (system pages refused) and the builder
 * frame route. Everything runs through the HTTP kernel as a Super Admin and is rolled back.
 * Run: php artisan tinker docs/checks/16.2-page-builder.php < /dev/null   (needs `db:seed` first)
 */

use App\Models\Page;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Queries\Admin\PageEditor;
use App\Support\Pages\PageChecklist;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

DB::beginTransaction();

try {
    // The kernel must not demand a CSRF token from this script.
    app()->instance('env', 'testing');

    $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    $admin->assignRole('Super Admin');
    auth()->setUser($admin);

    $call = function (string $method, string $uri, array $data = [], array $headers = []) use ($admin) {
        auth()->setUser($admin);
        $request = Request::create($uri, $method, $data, [], [], array_merge(['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'], $headers), $data === [] ? null : json_encode($data));
        $response = app(Kernel::class)->handle($request);
        $json = json_decode((string) $response->getContent(), true);

        return [$response->getStatusCode(), is_array($json) ? $json : [], $response];
    };

    $home = Page::query()->where('slug', 'home')->firstOrFail();
    $custom = Page::query()->where('is_system', false)->where('template', 'builder')->firstOrFail();

    // --- screens -------------------------------------------------------------------------------------------------
    $html = fn (string $uri): int => (function () use ($uri, $admin) {
        auth()->setUser($admin);

        return app(Kernel::class)->handle(Request::create($uri, 'GET'))->getStatusCode();
    })();
    $say('the pages list opens', $html('/admin/pages'), 200);
    $say('a system page opens in the editor', $html("/admin/pages/{$home->id}/edit"), 200);
    $say('a custom page opens in the editor', $html("/admin/pages/{$custom->id}/edit"), 200);
    $say('a new page opens', $html('/admin/pages/create'), 200);
    $say('the builder frame opens for an editor', $html('/preview/builder'), 200);

    // --- editor payload ---------------------------------------------------------------------------------------------
    $form = PageEditor::decorate(PageEditor::form($home));
    $say('the home page has blocks with ids and types', [count($form['blocks']) > 3, isset($form['blocks'][0]['id']), isset($form['blocks'][0]['type'])], [true, true, true]);
    $linked = collect($form['blocks'])->contains(fn ($block) => str_contains(json_encode($block['data']), '"link_type"'));
    $withState = collect($form['blocks'])->contains(fn ($block) => str_contains(json_encode($block['data']), '"link":{'));
    $say('links carry their target state for the picker', [$linked, $withState], [true, true]);
    $say('options hold the lists the forms need', array_values(array_intersect(['projects', 'areas', 'cities', 'segments', 'link_types', 'block_icons', 'block_limit'], array_keys(PageEditor::options()))), ['projects', 'areas', 'cities', 'segments', 'link_types', 'block_icons', 'block_limit']);

    // --- live preview -----------------------------------------------------------------------------------------------
    $blocks = array_map(fn (array $block): array => [...$block, 'id' => $block['id'] ?? -1], PageEditor::form($home)['blocks']);
    [$status, $out] = $call('POST', '/admin/pages/preview', ['page_id' => $home->id, 'title' => 'Home', 'blocks' => $blocks]);
    $say('preview: 200 with resolved blocks, errors and a checklist', [$status, count($out['blocks'] ?? []) === count($blocks), array_key_exists('errors', $out), count($out['checklist'] ?? []) >= 3], [200, true, true, true]);
    $say('preview: a valid page has no block errors', $out['errors'], []);

    $broken = [['id' => -7, 'type' => 'cta_band', 'data' => ['heading' => '', 'style' => 'brand'], 'is_visible' => true, 'anchor_id' => null, 'background' => 'default', 'spacing' => 'md', 'animate' => true]];
    [$status, $out] = $call('POST', '/admin/pages/preview', ['page_id' => null, 'title' => 'Draft', 'blocks' => $broken]);
    $say('preview: an unsaved page works and flags the invalid block by id', [$status, array_map('strval', array_keys($out['errors'])), isset($out['errors']['-7']['heading'])], [200, ['-7'], true]);
    $say('preview: the checklist says the first block is no opener', collect($out['checklist'])->firstWhere('key', 'h1')['passed'], false);

    [$status] = $call('POST', '/admin/pages/preview', ['page_id' => null, 'title' => 'x', 'blocks' => array_fill(0, 31, $broken[0])]);
    $say('preview: more than 30 blocks is refused', $status, 422);
    [$status] = $call('POST', '/admin/pages/preview', ['page_id' => null, 'title' => 'x', 'blocks' => [[...$broken[0], 'type' => 'nope']]]);
    $say('preview: an unknown block type is refused', $status, 422);

    // --- saving a custom page ------------------------------------------------------------------------------------------
    $form = PageEditor::form($custom);
    $original = array_column($form['blocks'], 'id');
    $reversed = array_reverse($form['blocks']);
    $reversed[0]['data']['_key'] = 'k1';
    $payload = [...$form, 'blocks' => $reversed, 'lock_version' => $custom->lock_version];
    [$status] = $call('PATCH', "/admin/pages/{$custom->id}", $payload);
    $custom->refresh()->load('blocks');
    $say('save: a draft or live page answers a redirect', in_array($status, [302, 303], true), true);
    // A published page stores the edit as its pending revision (CMS-PUB-002); any other page writes its rows.
    $saved = $custom->pending_revision_id !== null ? $custom->pendingRevisionOrNull()->payload['blocks'] : PageEditor::form($custom)['blocks'];
    $say('save: the block order follows the form', array_column($saved, 'id'), array_values(array_reverse($original)));
    $say('save: client keys are not stored', str_contains(json_encode($saved), '_key'), false);

    $payload['slug'] = 'projects';
    [$status, $out] = $call('PATCH', "/admin/pages/{$custom->id}", $payload);
    $say('save: a reserved slug is refused (422)', [$status, isset($out['errors']['slug'])], [422, true]);

    // --- duplicate ------------------------------------------------------------------------------------------------------
    [$status] = $call('POST', "/admin/pages/{$custom->id}/duplicate");
    $copy = Page::query()->where('slug', $custom->slug.'-copy')->first();
    $say('duplicate: a draft copy with a free slug and the same blocks', [in_array($status, [302, 303], true), $copy?->publish_status->value, $copy?->blocks()->count() === $custom->blocks()->count(), $copy?->title], [true, 'draft', true, 'Copy of '.$custom->title]);
    [$status] = $call('POST', "/admin/pages/{$home->id}/duplicate");
    $say('duplicate: a system page cannot be duplicated', $status, 403);

    // --- delete -------------------------------------------------------------------------------------------------------------
    [$status] = $call('DELETE', "/admin/pages/{$copy->id}");
    $say('delete: a custom page goes to the Recycle bin', [in_array($status, [302, 303], true), Page::query()->whereKey($copy->id)->exists(), RecycleBinEntry::query()->where('type', 'page')->where('trashable_id', $copy->id)->exists()], [true, false, true]);
    [$status] = $call('DELETE', "/admin/pages/{$home->id}");
    $say('delete: a system page is refused with its sentence and stays', [in_array($status, [302, 303], true), Page::query()->whereKey($home->id)->exists(), RecycleBinEntry::query()->where('type', 'page')->where('trashable_id', $home->id)->exists()], [true, true, false]);

    // --- checklist ----------------------------------------------------------------------------------------------------------
    $say('checklist: the seeded home page can be published', collect(app(PageChecklist::class)->items($home->load('blocks')))->every(fn ($item) => $item['passed']), true);
} finally {
    DB::rollBack();
}
