<?php

/**
 * Phase 10.2 check: sidebar items per role, 403 on forbidden screens, noindex header,
 * guest redirect, preferences, sign-out of other sessions. Everything is rolled back.
 * Needs `php artisan db:seed` first (roles + one user per role).
 * Run: php artisan tinker docs/checks/10.2-admin-shell.php
 */

use App\Actions\Users\SignOutOtherSessions;
use App\Actions\Users\UpdatePreferences;
use App\Models\User;
use App\Support\Admin\AdminNavigation;
use App\Support\Auth\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ').$label.': '.json_encode($got).($got === $expect ? '' : ' (expect '.json_encode($expect).')')."\n";
};
$as = fn (SystemRole $role) => User::query()->where('email', str($role->value)->slug().'@gemcon.test')->firstOrFail();
$keys = fn (User $user): array => collect(AdminNavigation::forUser($user))->flatMap(fn ($g) => collect($g['items'])->pluck('key'))->all();
$get = function (User $user, string $uri) {
    Auth::guard('web')->setUser($user);

    return app()->handle(Request::create($uri, 'GET'));
};

$content = ['projects', 'locations', 'pages', 'menus', 'news', 'testimonials', 'partners', 'faqs', 'team', 'offices', 'careers'];

DB::beginTransaction();

try {
    // Sidebar per role (spec/admin.md §10.2)
    $say('Super Admin sees all 24', count($keys($as(SystemRole::SuperAdmin))), 24);
    $say('Admin sees all but nothing role-only', count($keys($as(SystemRole::Admin))), 24);
    $say('Content Editor', $keys($as(SystemRole::ContentEditor)), ['dashboard', ...$content, 'subscribers', 'media', 'redirects', 'not-found', 'activity-log']);
    $say('Sales Manager', $keys($as(SystemRole::SalesManager)), ['dashboard', 'projects', 'leads', 'subscribers', 'media', 'activity-log']);
    $say('Sales Executive', $keys($as(SystemRole::SalesExecutive)), ['dashboard', 'projects', 'leads', 'activity-log']);
    $say('Viewer', $keys($as(SystemRole::Viewer)), ['dashboard', ...$content, 'leads', 'media', 'redirects', 'not-found', 'sitemaps']);

    // Routes: 200 where listed, 403 where hidden
    $say('Admin GET /admin', $get($as(SystemRole::Admin), '/admin')->getStatusCode(), 200);
    $say('Admin GET /admin/users', $get($as(SystemRole::Admin), '/admin/users')->getStatusCode(), 200);
    $say('Content Editor GET /admin/users', $get($as(SystemRole::ContentEditor), '/admin/users')->getStatusCode(), 403);
    $say('Content Editor GET /admin/leads', $get($as(SystemRole::ContentEditor), '/admin/leads')->getStatusCode(), 403);
    $say('Sales Executive GET /admin/leads', $get($as(SystemRole::SalesExecutive), '/admin/leads')->getStatusCode(), 200);
    $say('Sales Executive GET /admin/media', $get($as(SystemRole::SalesExecutive), '/admin/media')->getStatusCode(), 403);
    $say('Viewer GET /admin/settings', $get($as(SystemRole::Viewer), '/admin/settings')->getStatusCode(), 403);
    $say('Super Admin GET /admin/maintenance', $get($as(SystemRole::SuperAdmin), '/admin/maintenance')->getStatusCode(), 200);
    $say('Super Admin GET /admin/profile', $get($as(SystemRole::SuperAdmin), '/admin/profile')->getStatusCode(), 200);
    $say('Super Admin GET /admin/nope', $get($as(SystemRole::SuperAdmin), '/admin/nope')->getStatusCode(), 404);

    // Guest, noindex, old URLs
    Auth::guard('web')->forgetUser();
    $guest = app()->handle(Request::create('/admin', 'GET'));
    $say('guest /admin redirects to', $guest->headers->get('Location'), url('/admin/login'));
    $say('X-Robots-Tag on /admin', str_contains((string) $guest->headers->get('X-Robots-Tag'), 'noindex'), true);
    $say('GET /admin/login', app()->handle(Request::create('/admin/login', 'GET'))->getStatusCode(), 200);
    $say('GET /login (old URL)', app()->handle(Request::create('/login', 'GET'))->getStatusCode(), 404);
    $say('GET /register', app()->handle(Request::create('/register', 'GET'))->getStatusCode(), 404);

    // Preferences + other sessions
    $viewer = $as(SystemRole::Viewer);
    app(UpdatePreferences::class)->handle($viewer, ['theme' => 'dark', 'table_density' => 'compact']);
    $say('theme saved', $viewer->fresh()->theme, 'dark');
    $say('density saved', $viewer->fresh()->table_density, 'compact');

    foreach (['a', 'b', 'current'] as $id) {
        DB::table('sessions')->insert(['id' => "check-{$id}", 'user_id' => $viewer->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => time()]);
    }
    $say('other sessions ended', app(SignOutOtherSessions::class)->handle($viewer, 'check-current'), 2);
    $say('current session kept', DB::table('sessions')->where('id', 'check-current')->exists(), true);
} finally {
    DB::rollBack();
}
