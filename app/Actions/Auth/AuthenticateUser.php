<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Fortify's `authenticateUsing` callback (ADM-AUTH-001, ADM-AUTH-007).
 * A wrong email or password returns null (Fortify counts the failure); a
 * deactivated account is refused only after the password matched, so the
 * message does not reveal which emails exist.
 */
final class AuthenticateUser
{
    public function handle(Request $request): ?User
    {
        $user = User::query()
            ->where(Fortify::username(), Str::lower((string) $request->input(Fortify::username())))
            ->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return null;
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                Fortify::username() => __('This account is deactivated. Contact an administrator.'),
            ]);
        }

        return $user;
    }
}
