<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Users\InviteUser;
use App\Actions\Users\ResendInvitation;
use App\Actions\Users\SetUserActive;
use App\Actions\Users\SetUsersActive;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Queries\Admin\RecycleBinTable;
use App\Queries\Admin\UsersTable;
use App\Support\Toasts\Toast;
use App\Support\Users\UserGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-16 users tab. Controllers only authorise, validate, call an Action and respond (DEC-32).
 */
class UserController extends Controller
{
    public function index(Request $request, UsersTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', User::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/users/Index', [
            ...$table->props($request, $actor),
            'assignableRoles' => UserGuard::assignableRoles($actor),
            'canManageRoles' => $actor->can('roles.manage'),
            'canShareViews' => $actor->can('views.share'),
            'binCount' => RecycleBinTable::countFor($actor, User::binType()),
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Users & roles', 'href' => null],
            ],
        ]);
    }

    public function store(StoreUserRequest $request, InviteUser $inviteUser): RedirectResponse
    {
        $user = $inviteUser->handle(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('role')->toString(),
            $request->user(),
        );

        Toast::success('Invitation sent')->description($user->email)->flash();

        return back();
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle(
            $request->user(),
            $user,
            $request->string('name')->toString(),
            $request->filled('phone') ? $request->string('phone')->toString() : null,
            $request->string('role')->toString(),
        );

        Toast::success('User saved')->description($user->name)->flash();

        return back();
    }

    public function activate(Request $request, User $user, SetUserActive $setUserActive): RedirectResponse
    {
        return $this->setActive($request, $user, true, $setUserActive);
    }

    public function deactivate(Request $request, User $user, SetUserActive $setUserActive): RedirectResponse
    {
        return $this->setActive($request, $user, false, $setUserActive);
    }

    public function resend(Request $request, User $user, ResendInvitation $resendInvitation): RedirectResponse
    {
        Gate::authorize('update', $user);

        try {
            $resendInvitation->handle($request->user(), $user);
        } catch (ValidationException $exception) {
            return $this->refused($exception);
        }

        Toast::success('Invitation resent')->description($user->email)->flash();

        return back();
    }

    /** DEL-02: Delete = Recycle bin, with Undo (TOAST-02). */
    public function destroy(Request $request, User $user, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $user);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $user);
        } catch (ValidationException $exception) {
            return $this->refused($exception);
        }

        Toast::success('User moved to Recycle bin')
            ->description($user->name)
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    public function bulk(BulkUsersRequest $request, UsersTable $table, SetUsersActive $setUsersActive, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $users = $table->selection($request, $request->user())->limit(BulkUsersRequest::MAX_IDS * 20)->get();

        if ($request->input('action') === 'delete') {
            abort_unless($request->user()->can('users.delete'), 403);

            return $this->bulkDelete($request, $users, $moveMany);
        }

        $active = $request->input('action') === 'activate';
        $result = $setUsersActive->handle($request->user(), $users, $active);

        $count = $result['changed'];
        $noun = $count === 1 ? 'user' : 'users';
        $title = match (true) {
            $count === 0 => 'No users were changed',
            $active => "{$count} {$noun} activated",
            default => "{$count} {$noun} deactivated",
        };

        $this->bulkToast($title, $result['refused'])->flash();

        return back();
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function bulkDelete(BulkUsersRequest $request, Collection $users, MoveManyToRecycleBin $moveMany): RedirectResponse
    {
        $result = $moveMany->handle($request->user(), $users);
        $count = count($result['entries']);
        $title = $count === 0 ? 'No users were deleted' : $count.' '.($count === 1 ? 'user' : 'users').' moved to Recycle bin';

        $toast = $this->bulkToast($title, $result['refused']);

        if ($count > 0) {
            $toast->undo(route('admin.recycle-bin.restore-many'), [
                'ids' => array_map(fn ($entry): int => $entry->id, $result['entries']),
                'undo' => true,
            ]);
        }

        $toast->flash();

        return back();
    }

    /**
     * TOAST-06: partial success is reported honestly, with the reasons.
     *
     * @param  list<string>  $refused
     */
    private function bulkToast(string $title, array $refused): Toast
    {
        if ($refused === []) {
            return Toast::success($title);
        }

        return Toast::warning($title.' · '.count($refused).' skipped')
            ->description(implode(' ', array_slice($refused, 0, 3)));
    }

    private function setActive(Request $request, User $user, bool $active, SetUserActive $setUserActive): RedirectResponse
    {
        Gate::authorize('update', $user);

        try {
            $setUserActive->handle($request->user(), $user, $active);
        } catch (ValidationException $exception) {
            return $this->refused($exception);
        }

        $toast = $active
            ? Toast::success('User activated')->description($user->name.' can sign in again.')
            : Toast::success('User deactivated')->description($user->name.' was signed out.');

        if (! $active) {
            $toast->action('Undo', route('admin.users.activate', $user), 'post');
        }

        $toast->flash();

        return back();
    }

    /** A guard refusal the button should already have explained; shown as an error toast (TOAST-03). */
    private function refused(ValidationException $exception): RedirectResponse
    {
        Toast::error($exception->validator->errors()->first())->flash();

        return back();
    }
}
