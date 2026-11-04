<?php

/**
 * Phase 10.1 check: roles, lockout, deactivated users, invite flow, /register.
 * Run: php artisan tinker docs/checks/10.1-auth.php   (everything is rolled back)
 */

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Users\AcceptInvitation;
use App\Actions\Users\InviteUser;
use App\Models\User;
use App\Support\Auth\LoginThrottle;
use App\Support\Auth\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};
$status = fn (string $method, string $uri) => app()->handle(Request::create($uri, $method))->getStatusCode();

DB::beginTransaction();

try {
    // /register does not exist
    $say('GET /register', $status('GET', '/register'), 404);
    $say('POST /register', in_array($status('POST', '/register'), [404, 405, 419], true), true);

    // Roles (needs `db:seed` first)
    $as = fn (SystemRole $role) => User::query()->where('email', str($role->value)->slug().'@gemcon.test')->firstOrFail();
    $can = fn (SystemRole $role, string $ability): bool => Gate::forUser($as($role))->allows($ability);

    $say('Super Admin anything', $can(SystemRole::SuperAdmin, 'made.up.ability'), true);
    $say('Admin users.manage', $can(SystemRole::Admin, 'users.manage'), true);
    $say('Admin roles.manage', $can(SystemRole::Admin, 'roles.manage'), false);
    $say('Admin settings.integrations', $can(SystemRole::Admin, 'settings.integrations'), false);
    $say('Content Editor projects.publish', $can(SystemRole::ContentEditor, 'projects.publish'), true);
    $say('Content Editor projects.delete', $can(SystemRole::ContentEditor, 'projects.delete'), false);
    $say('Content Editor leads.view-all', $can(SystemRole::ContentEditor, 'leads.view-all'), false);
    $say('Sales Manager leads.assign', $can(SystemRole::SalesManager, 'leads.assign'), true);
    $say('Sales Manager projects.write', $can(SystemRole::SalesManager, 'projects.write'), false);
    $say('Sales Executive leads.assign', $can(SystemRole::SalesExecutive, 'leads.assign'), false);
    $say('Viewer leads.view-all', $can(SystemRole::Viewer, 'leads.view-all'), true);
    $say('Viewer leads.view-contact (masked)', $can(SystemRole::Viewer, 'leads.view-contact'), false);

    // Login: wrong/right password, deactivated
    $viewer = $as(SystemRole::Viewer);
    $login = fn (string $email, string $password) => Request::create('/login', 'POST', ['email' => $email, 'password' => $password]);
    $auth = app(AuthenticateUser::class);
    $say('wrong password', $auth->handle($login($viewer->email, 'nope')), null);
    $say('right password', $auth->handle($login($viewer->email, 'password'))?->is($viewer), true);
    $viewer->forceFill(['is_active' => false])->save();
    try {
        $auth->handle($login($viewer->email, 'password'));
        $say('deactivated', 'logged in', 'ValidationException');
    } catch (ValidationException) {
        $say('deactivated', 'ValidationException', 'ValidationException');
    }
    $viewer->forceFill(['is_active' => true])->save();

    // Lockout: 5 failures in a minute → locked ~15 min
    $throttle = app(LoginThrottle::class);
    $request = Request::create('/login', 'POST', ['email' => 'lock@gemcon.test'], server: ['REMOTE_ADDR' => '10.0.0.9']);
    $throttle->clear($request);
    for ($i = 1; $i <= 4; $i++) {
        $throttle->increment($request);
    }
    $say('locked after 4 failures', $throttle->tooManyAttempts($request), false);
    $throttle->increment($request);
    $say('locked after 5 failures', $throttle->tooManyAttempts($request), true);
    $say('lockout ≈ 15 min', $throttle->availableIn($request) > 840 && $throttle->availableIn($request) <= 900, true);
    $say('lockout logged', DB::table('activity_log')->where('event', 'lockout')->exists(), true);
    $throttle->clear($request);

    // Invite → set password
    $invite = app(InviteUser::class)->handle('Check Person', 'invitee@gemcon.test', SystemRole::Viewer);
    $say('invitee unverified', $invite->email_verified_at, null);
    $url = URL::temporarySignedRoute('invitation.show', now()->addHours(72), ['user' => $invite->id]);
    $say('invite page', $status('GET', $url), 200);
    $say('unsigned link', $status('GET', route('invitation.show', $invite, false)), 403);
    $expired = URL::temporarySignedRoute('invitation.show', now()->subMinute(), ['user' => $invite->id]);
    $say('expired link', $status('GET', $expired), 403);
    app(AcceptInvitation::class)->handle($invite, 'Another-Pass-9876');
    $say('invitee verified', $invite->fresh()->email_verified_at !== null, true);
    $say('used link redirects', $status('GET', $url), 302);
    $say('new password works', $auth->handle($login($invite->email, 'Another-Pass-9876'))?->is($invite), true);
} finally {
    DB::rollBack();
}
