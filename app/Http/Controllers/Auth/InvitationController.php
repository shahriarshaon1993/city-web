<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Users\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-AUTH-006: set-password page behind the signed 72-hour invite link.
 * Both routes carry the `signed` middleware; the form posts back to the same
 * signed URL.
 */
class InvitationController extends Controller
{
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if ($used = $this->alreadyAccepted($user)) {
            return $used;
        }

        return Inertia::render('auth/AcceptInvitation', [
            'email' => $user->email,
            'name' => $user->name,
            'submitUrl' => $request->fullUrl(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(AcceptInvitationRequest $request, User $user, AcceptInvitation $accept): RedirectResponse
    {
        if ($used = $this->alreadyAccepted($user)) {
            return $used;
        }

        $accept->handle($user, $request->string('password')->toString());

        return to_route('login')->with('status', __('Password saved. Sign in to continue.'));
    }

    private function alreadyAccepted(User $user): ?RedirectResponse
    {
        if ($user->email_verified_at === null && $user->is_active) {
            return null;
        }

        return to_route('login')->with('status', __('This invitation was already used or is no longer valid. Sign in, or ask an administrator for a new invitation.'));
    }
}
