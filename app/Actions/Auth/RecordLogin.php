<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Stamps `last_login_at` and logs "login" once per session (ADM-LOG-002):
 * the Login event can fire again within one session (remember cookie, re-auth).
 */
final class RecordLogin
{
    private const string SESSION_FLAG = 'auth.login_logged';

    public function __construct(private readonly RecordAuthEvent $record) {}

    public function handle(User $user, Request $request): void
    {
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if (! $request->hasSession() || $request->session()->get(self::SESSION_FLAG)) {
            return;
        }

        $request->session()->put(self::SESSION_FLAG, true);

        $this->record->handle('login', 'Signed in', $user, $request);
    }
}
