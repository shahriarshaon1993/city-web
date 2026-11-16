<?php

/**
 * Phase 11.2 check: folder rules (≤ 3 levels, sibling names, move into itself, delete only when
 * empty), Replace everywhere (usages + the avatar column), the library filters and the delete
 * guard. Everything runs inside a transaction that is rolled back; no files are written.
 * Needs `php artisan db:seed` (one user per role).
 * Run: php artisan tinker docs/checks/11.2-media-library.php
 */

use App\Actions\Media\CreateMediaFolder;
use App\Actions\Media\DeleteMediaFolder;
use App\Actions\Media\MoveMediaAssets;
use App\Actions\Media\MoveMediaFolder;
use App\Actions\Media\RenameMediaFolder;
use App\Actions\Media\ReplaceMediaEverywhere;
use App\Actions\Media\SyncMediaUsages;
use App\Actions\Media\UpdateMediaAsset;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\User;
use App\Queries\Admin\MediaTable;
use App\Support\Auth\SystemRole;
use App\Support\Media\FolderTree;
use App\Support\Media\MediaKind;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ').$label.': '.json_encode($got).($got === $expect ? '' : ' (expect '.json_encode($expect).')')."\n";
};
$refused = function (callable $fn): ?string {
    try {
        $fn();
    } catch (ValidationException $exception) {
        return $exception->validator->errors()->first();
    }

    return null;
};

DB::beginTransaction();

try {
    $admin = User::role(SystemRole::SuperAdmin->value)->orderBy('id')->firstOrFail();
    $create = app(CreateMediaFolder::class);
    $move = app(MoveMediaFolder::class);

    // --- folders ----------------------------------------------------------------------------
    $a = $create->handle($admin, '  Check  Projects ', null);
    $say('name is trimmed and squeezed', $a->name, 'Check Projects');
    $b = $create->handle($admin, 'Orchid', $a->id);
    $c = $create->handle($admin, 'Interiors', $b->id);
    $say('level 3 depth', $c->depth(), 3);
    $say('expect refusal: a 4th level', $refused(fn () => $create->handle($admin, 'Too deep', $c->id)) !== null, true);
    $say('expect refusal: same name among siblings (any case)', $refused(fn () => $create->handle($admin, 'check projects', null)) !== null, true);
    $say('expect refusal: move into own subfolder', $refused(fn () => $move->handle($admin, $a, $c->id)) !== null, true);

    $d = $create->handle($admin, 'Check Other', null);
    $say('expect refusal: moving a 3-level tree under a folder (4 levels)', $refused(fn () => $move->handle($admin, $a, $d->id)) !== null, true);
    $move->handle($admin, $c, $d->id);
    $say('move a leaf to another root', $c->fresh()->parent_id, $d->id);

    app(RenameMediaFolder::class)->handle($admin, $d, 'Check Renamed');
    $say('rename', $d->fresh()->name, 'Check Renamed');

    $say('expect refusal: delete a folder with a subfolder', $refused(fn () => app(DeleteMediaFolder::class)->handle($admin, $d)) !== null, true);

    // --- assets (rows only; no file needed for these rules) ---------------------------------
    $img = MediaAsset::query()->create(['kind' => MediaKind::Image, 'title' => 'Check image', 'focal_x' => 50, 'focal_y' => 50, 'is_decorative' => false, 'conversions_status' => 'ready', 'uploaded_by' => $admin->id]);
    $img2 = MediaAsset::query()->create(['kind' => MediaKind::Image, 'title' => 'Check image two', 'alt' => 'Two', 'focal_x' => 50, 'focal_y' => 50, 'is_decorative' => false, 'conversions_status' => 'ready', 'uploaded_by' => $admin->id]);
    $pdf = MediaAsset::query()->create(['kind' => MediaKind::Document, 'title' => 'Check brochure', 'focal_x' => 50, 'focal_y' => 50, 'is_decorative' => false, 'conversions_status' => 'ready', 'uploaded_by' => $admin->id]);

    $moved = app(MoveMediaAssets::class)->handle($admin, [$img, $img2], $b);
    $say('bulk move to folder', [$moved, $img->fresh()->folder_id], [2, $b->id]);
    $say('expect refusal: delete a folder with files', $refused(fn () => app(DeleteMediaFolder::class)->handle($admin, $b)) !== null, true);

    $tree = FolderTree::build();
    $node = collect($tree['folders'])->firstWhere('id', $a->id);
    $say('tree: child count of Orchid', $node['children'][0]['count'], 2);
    $say('tree: Orchid delete block names the files', str_contains((string) $node['children'][0]['delete_block'], '2 files'), true);

    // --- metadata ----------------------------------------------------------------------------
    $changed = app(UpdateMediaAsset::class)->handle($admin, $img, [
        'title' => 'Check image', 'alt' => 'ignored', 'caption' => null, 'credit' => null, 'is_decorative' => true,
        'tags' => ['facade'], 'folder_id' => $b->id, 'focal_x' => 30, 'focal_y' => 70,
    ]);
    $say('decorative clears alt', $img->fresh()->alt, null);
    $say('focal saved', [$img->fresh()->focal_x, $img->fresh()->focal_y], [30, 70]);
    $say('changed fields reported', in_array('focal_x', $changed, true), true);

    // --- usages, guard, Replace everywhere ---------------------------------------------------
    $admin->forceFill(['avatar_asset_id' => $img->id])->save();
    app(SyncMediaUsages::class)->handle($admin, 'avatar', [$img->id]);
    $say('guard blocks a used file', $img->binUsageBlock($admin) !== null, true);
    $say('expect refusal: replace an image with a PDF', $refused(fn () => app(ReplaceMediaEverywhere::class)->handle($admin, $img, $pdf)) !== null, true);

    $count = app(ReplaceMediaEverywhere::class)->handle($admin, $img, $img2);
    $say('replace moves the usage', [$count, MediaUsage::query()->where('media_asset_id', $img2->id)->count()], [1, 1]);
    $say('replace updates users.avatar_asset_id', $admin->fresh()->avatar_asset_id, $img2->id);
    $say('old file is free again', $img->fresh()->binUsageBlock($admin), null);

    // --- library filters ------------------------------------------------------------------------
    $table = app(MediaTable::class);
    $ids = fn (array $query) => collect($table->props(Request::create('/admin/media', 'GET', $query), $admin)['rows'])->pluck('id')->all();
    $say('filter folder', $ids(['filter' => ['folder' => (string) $b->id]]), [$img2->id, $img->id]);
    $say('filter unfiled has the PDF', in_array($pdf->id, $ids(['filter' => ['folder' => 'unfiled']]), true), true);
    $say('filter missing alt excludes decorative + filled', array_values(array_intersect($ids(['filter' => ['missing_alt' => '1']]), [$img->id, $img2->id])), []);
    $say('filter kind=document', in_array($pdf->id, $ids(['filter' => ['kind' => 'document']]), true), true);
    $say('search by tag', $ids(['search' => 'facade']), [$img->id]);
    $say('per_page 48 accepted, 25 refused', [
        $table->props(Request::create('/admin/media', 'GET', ['per_page' => 48]), $admin)['meta']['per_page'],
        $table->props(Request::create('/admin/media', 'GET', ['per_page' => 25]), $admin)['meta']['per_page'],
    ], [48, 24]);
} finally {
    DB::rollBack();
    echo "rolled back\n";
}
