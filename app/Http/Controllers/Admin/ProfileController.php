<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\ChangePassword;
use App\Actions\Users\SignOutOtherSessions;
use App\Actions\Users\UpdatePreferences;
use App\Actions\Users\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SignOutOtherSessionsRequest;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdatePreferencesRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Support\Auth\UserSessions;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-20. Every signed-in user edits their own profile, so there is no
 * permission to check beyond the `auth` middleware of the route group.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/Profile', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'sessions' => UserSessions::for($user, $request->session()->getId()),
            'lastSaved' => $user->updated_at?->toIso8601String(),
            // CMS-MED-010: the picture field shows the chosen library item.
            'avatar' => $user->avatar === null ? null : (new MediaAssetResource($user->avatar->load('media')))->resolve($request),
            'breadcrumbs' => [
                ['title' => 'Account', 'href' => null],
                ['title' => 'Profile', 'href' => null],
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $emailChanged = $updateProfile->handle(
            $request->user(),
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->filled('phone') ? $request->string('phone')->toString() : null,
            $request->integer('avatar_asset_id') ?: null,
        );

        $toast = Toast::success('Profile saved');

        if ($emailChanged) {
            $toast->description('Confirm your new email address with the link we sent.');
        }

        $toast->flash();

        return $emailChanged ? to_route('verification.notice') : back();
    }

    public function updatePassword(UpdatePasswordRequest $request, ChangePassword $changePassword): RedirectResponse
    {
        $changePassword->handle($request->user(), $request->string('password')->toString());

        Toast::success('Password changed')->flash();

        return back();
    }

    /**
     * Theme and density. The theme also goes into the `appearance` cookie that
     * the admin root reads before first paint (no light flash on reload).
     */
    public function updatePreferences(UpdatePreferencesRequest $request, UpdatePreferences $updatePreferences): RedirectResponse
    {
        $updatePreferences->handle($request->user(), $request->validated());

        if ($request->has('theme')) {
            // Not HttpOnly: the topbar toggle also writes this cookie from JavaScript.
            Cookie::queue(cookie('appearance', $request->string('theme')->toString(), 60 * 24 * 365, httpOnly: false));
        }

        return back();
    }

    public function destroyOtherSessions(SignOutOtherSessionsRequest $request, SignOutOtherSessions $signOut): RedirectResponse
    {
        $ended = $signOut->handle($request->user(), $request->session()->getId());

        $toast = match (true) {
            $ended === 0 => Toast::info('No other sessions were signed in'),
            $ended === 1 => Toast::success('Signed out 1 other session'),
            default => Toast::success("Signed out {$ended} other sessions"),
        };

        $toast->flash();

        return back();
    }
}
