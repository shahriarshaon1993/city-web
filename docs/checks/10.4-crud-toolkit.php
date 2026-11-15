<?php

/**
 * Phase 10.4 check: Recycle bin (move, restore, delete forever, nightly purge), the guards, the
 * bin permissions, the toast payload and the slug availability answers. Everything is rolled
 * back. Needs `php artisan db:seed` first (roles + one user per role) and the 10.4 migration.
 * Run: php artisan tinker docs/checks/10.4-crud-toolkit.php
 */

use App\Actions\RecycleBin\DeleteForever;
use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\RecycleBin\PurgeRecycleBin;
use App\Actions\RecycleBin\RestoreFromRecycleBin;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Queries\Admin\RecycleBinTable;
use App\Support\Auth\SystemRole;
use App\Support\RecycleBin\BinTypes;
use App\Support\Slugs\SlugAvailability;
use App\Support\Toasts\Toast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ').$label.': '.json_encode($got).($got === $expect ? '' : ' (expect '.json_encode($expect).')')."\n";
};
$as = fn (SystemRole $role) => User::role($role->value)->where('is_active', true)->orderBy('id')->firstOrFail();
$refused = function (callable $fn): ?string {
    try {
        $fn();

        return null;
    } catch (ValidationException $exception) {
        return $exception->validator->errors()->first();
    }
};

DB::beginTransaction();

try {
    $super = $as(SystemRole::SuperAdmin);
    $admin = $as(SystemRole::Admin);

    // --- permissions (spec/admin.md §10.2, crud.md Users row, DEL-07)
    $say('Super Admin may delete users', $super->can('users.delete'), true);
    $say('Admin may not delete users (Bin is Super Admin only)', $admin->can('users.delete'), false);
    $say('Admin sees no bin type', BinTypes::typesFor($admin), []);
    $say('Super Admin sees the user type', BinTypes::typesFor($super), ['user']);

    // --- move to bin
    $victim = User::factory()->create(['name' => 'Bin Check', 'email' => 'bin-check@gemcon.test']);
    DB::table('sessions')->insert(['id' => 'bin-check-session', 'user_id' => $victim->id, 'payload' => '', 'last_activity' => time()]);

    $entry = app(MoveToRecycleBin::class)->handle($super, $victim);
    $say('user is soft deleted', User::withTrashed()->find($victim->id)->trashed(), true);
    $say('entry keeps the name', $entry->label, 'Bin Check');
    $say('entry type = user', $entry->type, 'user');
    $say('entry records who deleted', $entry->deleted_by, $super->id);
    $say('users never auto-delete (purge_at null)', $entry->purge_at, null);
    $say('sessions ended', DB::table('sessions')->where('user_id', $victim->id)->count(), 0);
    $say('activity: moved to bin', Activity::query()->where('log_name', 'users')->where('event', 'deleted')->where('subject_id', $victim->id)->exists(), true);

    // --- guards (DEL-03)
    $say('cannot delete yourself', $refused(fn () => app(MoveToRecycleBin::class)->handle($super, $super)), 'You cannot delete your own account.');
    $many = app(MoveManyToRecycleBin::class)->handle($super, collect([$super]));
    $say('bulk: refused users are explained, none moved', [count($many['entries']), $many['refused']], [0, [$super->name.': You cannot delete your own account.']]);

    // --- table + counts
    $request = Request::create('/admin/recycle-bin', 'GET', ['filter' => ['type' => 'user']]);
    $request->setUserResolver(fn () => $super);
    $page = app(RecycleBinTable::class)->props($request, $super);
    $say('bin table lists the entry', collect($page['rows'])->pluck('label')->contains('Bin Check'), true);
    $say('row says never', collect($page['rows'])->firstWhere('label', 'Bin Check')['purge_at'], null);
    $say('count for Super Admin includes it', RecycleBinTable::countFor($super) >= 1, true);
    $say('count for Admin is 0', RecycleBinTable::countFor($admin), 0);

    // --- DEL-08: a taken email in the bin is named
    $message = (new StoreUserRequest)->merge(['email' => 'bin-check@gemcon.test'])->messages()['email.unique'];
    $say('DEL-08 message names the bin', str_contains($message, 'Recycle bin'), true);

    // --- restore
    $restored = app(RestoreFromRecycleBin::class)->handle($super, $entry);
    $say('restored user is live again', User::find($victim->id)?->trashed(), false);
    $say('entry removed after restore', RecycleBinEntry::query()->whereKey($entry->id)->exists(), false);
    $say('restore returns the item', $restored->is($victim), true);

    // --- delete forever (no personal data in the log, DEL-07)
    $entry = app(MoveToRecycleBin::class)->handle($super, $victim->refresh());
    app(DeleteForever::class)->handle($super, $entry);
    $say('forever: user row gone', User::withTrashed()->find($victim->id), null);
    $log = Activity::query()->where('log_name', 'users')->where('event', 'deleted_forever')->latest('id')->first();
    $say('forever: activity entry exists', $log !== null, true);
    $say('forever: no name in the log', str_contains(json_encode($log->properties), 'Bin Check'), false);

    // --- nightly purge (DEL-06)
    $stale = User::factory()->create(['email' => 'stale@gemcon.test']);
    $staleEntry = app(MoveToRecycleBin::class)->handle($super, $stale);
    $say('purge ignores entries without a date', app(PurgeRecycleBin::class)->handle(), 0);
    $staleEntry->forceFill(['purge_at' => now()->subDay()])->save();
    $say('purge removes entries past their date', app(PurgeRecycleBin::class)->handle(), 1);
    $say('purged user is gone', User::withTrashed()->find($stale->id), null);
    $say('purge is logged without a causer', Activity::query()->where('event', 'purged')->latest('id')->first()?->causer_id, null);

    // --- toast payload (TOAST-01/02)
    $toast = Toast::success('User moved to Recycle bin')->description('Name')->undo('/admin/recycle-bin/1/restore', ['undo' => true])->toArray();
    $say('toast shape', array_keys($toast), ['type', 'title', 'description', 'action']);
    $say('toast undo action', [$toast['action']['label'], $toast['action']['method']], ['Undo', 'post']);
    $say('toast without action', Toast::info('Hi')->toArray()['action'], null);

    // --- slug availability (VAL-04)
    $slugs = app(SlugAvailability::class);
    $say('slug: reserved', $slugs->check('site', 'admin')['available'], false);
    $say('slug: bad format', $slugs->check('site', 'Not A Slug')['available'], false);
    $say('slug: free', $slugs->check('site', 'our-story')['available'], true);
    $say('slug: reserved suggestion', $slugs->check('site', 'projects')['suggestion'], 'projects-2');
    $say('slug sources', SlugAvailability::sourceKeys(), ['site']);
} finally {
    DB::rollBack();
}
