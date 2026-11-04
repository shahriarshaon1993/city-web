<?php

namespace App\Http\Controllers\Admin;

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
use App\Queries\Admin\UsersTable;
use App\Support\Users\UserGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $user->email])]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $user->name])]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation resent to :email.', ['email' => $user->email])]);

        return back();
    }

    public function bulk(BulkUsersRequest $request, UsersTable $table, SetUsersActive $setUsersActive): RedirectResponse
    {
        $active = $request->input('action') === 'activate';

        $result = $setUsersActive->handle(
            $request->user(),
            $table->selection($request, $request->user())->limit(BulkUsersRequest::MAX_IDS * 20)->get(),
            $active,
        );

        $verb = $active ? __('Activated') : __('Deactivated');
        $message = trans_choice('{0} No users were changed.|{1} :verb 1 user.|[2,*] :verb :count users.', $result['changed'], ['verb' => $verb]);

        if ($result['refused'] !== []) {
            $message .= ' '.__(':count skipped — :reasons', ['count' => count($result['refused']), 'reasons' => implode(' ', array_slice($result['refused'], 0, 3))]);
        }

        Inertia::flash('toast', ['type' => $result['refused'] === [] ? 'success' : 'warning', 'message' => $message]);

        return back();
    }

    private function setActive(Request $request, User $user, bool $active, SetUserActive $setUserActive): RedirectResponse
    {
        Gate::authorize('update', $user);

        try {
            $setUserActive->handle($request->user(), $user, $active);
        } catch (ValidationException $exception) {
            return $this->refused($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $active ? __(':name is active again.', ['name' => $user->name]) : __(':name was deactivated and signed out.', ['name' => $user->name]),
        ]);

        return back();
    }

    /** A guard refusal the button should already have explained; shown as an error toast (TOAST-01). */
    private function refused(ValidationException $exception): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $exception->validator->errors()->first()]);

        return back();
    }
}
