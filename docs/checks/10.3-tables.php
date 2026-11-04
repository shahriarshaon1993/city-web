<?php

/**
 * Phase 10.3 check: table state sanitising, filters/sorts/search of the users, roles and
 * activity tables, user guards, role rules, view state and the export rows.
 * Everything is rolled back. Needs `php artisan db:seed` first (roles + one user per role).
 * Run: php artisan tinker docs/checks/10.3-tables.php
 */

use App\Actions\Roles\DeleteRole;
use App\Actions\Roles\SaveRole;
use App\Actions\Tables\SaveTableView;
use App\Actions\Users\SetUserActive;
use App\Actions\Users\UpdateUser;
use App\Models\SavedTableView;
use App\Models\User;
use App\Queries\Admin\ActivityLogTable;
use App\Queries\Admin\RolesTable;
use App\Queries\Admin\UsersTable;
use App\Support\Auth\SystemRole;
use App\Support\Tables\TableState;
use App\Support\Tables\ViewState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ').$label.': '.json_encode($got).($got === $expect ? '' : ' (expect '.json_encode($expect).')')."\n";
};
$as = fn (SystemRole $role) => User::query()->where('email', str($role->value)->slug().'@gemcon.test')->firstOrFail();
$refused = function (callable $fn): ?string {
    try {
        $fn();

        return null;
    } catch (ValidationException $exception) {
        return $exception->validator->errors()->first();
    }
};
$req = fn (array $query) => Request::create('/admin/users', 'GET', $query);
// Resources read the signed-in user from the request, as in a real call.
$props = function ($table, array $query, User $actor): array {
    $request = Request::create('/admin/users', 'GET', $query);
    $request->setUserResolver(fn () => $actor);

    return $table->props($request, $actor);
};

DB::beginTransaction();

try {
    $super = $as(SystemRole::SuperAdmin);
    $admin = $as(SystemRole::Admin);
    $users = app(UsersTable::class);

    // --- TableState (ADM-TBL-001/002/011)
    $state = $users->state($req(['page' => '0', 'per_page' => '7', 'sort' => '-name,evil,email,created_at,name,-is_active', 'filter' => ['role' => 'Admin', 'hacker' => 'x'], 'search' => '  hi  ']));
    $say('page clamps to 1', $state->page, 1);
    $say('per_page outside 10/25/50/100 → 25', $state->perPage, 25);
    $say('sort: unknown dropped, duplicate dropped, max 3', $state->sorts, ['-name', 'email', 'created_at']);
    $say('filter: unknown dropped', $state->filters, ['role' => 'Admin']);
    $say('search trimmed', $state->search, 'hi');
    $say('no sort → default', $users->state($req([]))->sorts, ['name']);

    // --- Users table queries
    $page = $props($users, ['sort' => '-name', 'per_page' => '10'], $super);
    $say('users total = seeded users', $page['meta']['total'], User::count());
    $say('users sorted desc', $page['rows'][0]['name'] >= $page['rows'][1]['name'], true);
    $say('filter role=Viewer', collect($props($users, ['filter' => ['role' => 'Viewer']], $super)['rows'])->pluck('role')->unique()->all(), ['Viewer']);
    $say('search by email', count($props($users, ['search' => 'viewer@gemcon'], $super)['rows']), 1);
    $say('status=invited (seeded users are verified)', $props($users, ['filter' => ['status' => 'invited']], $super)['meta']['total'], 0);
    $say('date range in the far future → 0', $props($users, ['filter' => ['created_from' => '2099-01-01']], $super)['meta']['total'], 0);
    $say('page past the end clamps to last page', $props($users, ['page' => '99', 'per_page' => '10'], $super)['meta']['page'], 1);
    $say('filter options carry counts', $users->filterOptions($super)['role'][0]['count'] >= 0, true);

    // --- user guards (UserGuard through the Actions)
    $say('cannot deactivate yourself', $refused(fn () => app(SetUserActive::class)->handle($super, $super, false)), 'You cannot deactivate your own account.');
    $say('Admin cannot deactivate a Super Admin', $refused(fn () => app(SetUserActive::class)->handle($admin, $super, false)), 'Only a Super Admin can change a Super Admin.');
    $say('Admin cannot promote to Super Admin', $refused(fn () => app(UpdateUser::class)->handle($admin, $as(SystemRole::Viewer), 'Test Viewer', null, 'Super Admin')), 'Only a Super Admin can assign the Super Admin role.');
    $say('last Super Admin cannot be demoted', $refused(function () use ($super): void {
        $other = User::factory()->create();
        $other->assignRole('Admin');
        app(UpdateUser::class)->handle($other, $super, $super->name, null, 'Admin');
    }) !== null, true);
    $viewer = $as(SystemRole::Viewer);
    $say('deactivate Viewer', app(SetUserActive::class)->handle($super, $viewer, false), true);
    $say('Viewer has no sessions left', DB::table('sessions')->where('user_id', $viewer->id)->count(), 0);
    $say('activate Viewer', app(SetUserActive::class)->handle($super, $viewer->refresh(), true), true);

    // --- roles
    $roles = app(RolesTable::class);
    $say('roles total = 6 system roles', $props($roles, [], $super)['meta']['total'], 6);
    $custom = app(SaveRole::class)->handle($super, null, 'Check HR', ['jobs.view']);
    $say('custom role always has dashboard.view', $custom->permissions->pluck('name')->sort()->values()->all(), ['dashboard.view', 'jobs.view']);
    $say('type=custom filter', $props($roles, ['filter' => ['type' => 'custom']], $super)['meta']['total'], 1);
    $say('users range filter', $props($roles, ['filter' => ['users_min' => '1']], $super)['meta']['total'], 6);
    $say('system role cannot be edited', $refused(fn () => app(SaveRole::class)->handle($super, Role::findByName('Admin'), 'Admin', [])), 'System roles are fixed.');
    $say('system role cannot be deleted', $refused(fn () => app(DeleteRole::class)->handle($super, Role::findByName('Viewer'))), 'System roles cannot be deleted.');
    $viewer->assignRole('Check HR');
    $say('role with users cannot be deleted', str_contains((string) $refused(fn () => app(DeleteRole::class)->handle($super, $custom)), 'have this role') || str_contains((string) $refused(fn () => app(DeleteRole::class)->handle($super, $custom)), 'has this role'), true);
    $viewer->removeRole('Check HR');
    app(DeleteRole::class)->handle($super, $custom);
    $say('custom role deleted', Role::query()->where('name', 'Check HR')->exists(), false);

    // --- saved views (ADM-TBL-010)
    $say('view state drops unknown keys and page', ViewState::clean(['page' => 3, 'view' => 9, 'evil' => 'x', 'sort' => '-name', 'filter' => ['role' => 'Admin', 'BAD KEY' => 'x']]), ['filter' => ['role' => 'Admin'], 'sort' => '-name']);
    $view = app(SaveTableView::class)->handle($admin, 'users', 'Admins', ['filter' => ['role' => 'Admin'], 'sort' => '-name'], false, true);
    $say('default view redirect on a bare URL', str_contains((string) $users->viewRedirect(Request::create('/admin/users', 'GET'), $admin), 'view='.$view->id), true);
    $say('?view= alone expands', str_contains((string) $users->viewRedirect(Request::create('/admin/users', 'GET', ['view' => $view->id]), $admin), 'filter%5Brole%5D=Admin'), true);
    $say('no redirect when the URL has state', $users->viewRedirect(Request::create('/admin/users', 'GET', ['sort' => 'name']), $admin), null);
    $say("another user's personal view is not shared", $users->viewRedirect(Request::create('/admin/users', 'GET', ['view' => $view->id]), $super), null);
    $say('only the default flag moves', app(SaveTableView::class)->handle($admin, 'users', 'Second', [], false, true)->is_default && ! SavedTableView::find($view->id)->is_default, true);

    // --- activity log scoping + export rows (ADM-TBL-012, ADM-LOG-001)
    $log = app(ActivityLogTable::class);
    $say('Super Admin sees everyone', ActivityLogTable::canViewAll($super), true);
    $own = $as(SystemRole::SalesExecutive);
    activity('check')->causedBy($own)->event('created')->log('own row');
    activity('check')->causedBy($super)->event('created')->log('other row');
    $say('view-own scoping', collect($props($log, ['filter' => ['log' => 'check']], $own)['rows'])->pluck('description')->all(), ['own row']);
    $say('view-all sees both', $props($log, ['filter' => ['log' => 'check']], $super)['meta']['total'], 2);
    $columns = $log->exportColumnIds(['description', 'nope', 'created_at']);
    $say('export column ids keep order, drop unknown', $columns, ['description', 'created_at']);
    $say('export header', $log->exportHeader($columns), ['What', 'When']);
    $cells = iterator_to_array($log->exportRows($req(['filter' => ['log' => 'check']]), $super, $columns), false);
    $say('export rows', count($cells), 2);
    $say('export row cells', array_column($cells, 0), ['other row', 'own row']);
    $say('unknown export columns → all', count($log->exportColumnIds(['zzz'])), count($log->exportColumns()));
    $say('table state class is final readonly', (new ReflectionClass(TableState::class))->isReadOnly(), true);
} finally {
    DB::rollBack();
}
