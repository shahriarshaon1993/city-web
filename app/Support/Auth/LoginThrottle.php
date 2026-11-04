<?php

namespace App\Support\Auth;

use App\Actions\Auth\RecordAuthEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;

/**
 * ADM-AUTH-001: 5 failed attempts within a minute per email + IP start a
 * 15-minute lockout (logged once). Replaces Fortify's plain 5/minute limiter.
 */
class LoginThrottle extends LoginRateLimiter
{
    private const int MAX_ATTEMPTS = 5;

    private const int WINDOW_SECONDS = 60;

    private const int LOCKOUT_SECONDS = 15 * 60;

    public function tooManyAttempts(Request $request): bool
    {
        return $this->limiter->tooManyAttempts($this->lockoutKey($request), 1);
    }

    public function increment(Request $request): void
    {
        $this->limiter->hit($this->throttleKey($request), self::WINDOW_SECONDS);

        if ($this->limiter->attempts($this->throttleKey($request)) < self::MAX_ATTEMPTS) {
            return;
        }

        $this->limiter->hit($this->lockoutKey($request), self::LOCKOUT_SECONDS);
        $this->limiter->clear($this->throttleKey($request));

        $email = mb_strtolower((string) $request->input('email'));

        app(RecordAuthEvent::class)->handle(
            'lockout',
            'Login locked for 15 minutes after repeated failures',
            User::query()->where('email', $email)->first(),
            $request,
            ['email' => $email],
        );
    }

    public function availableIn(Request $request): int
    {
        return $this->limiter->availableIn($this->lockoutKey($request));
    }

    public function clear(Request $request): void
    {
        $this->limiter->clear($this->throttleKey($request));
        $this->limiter->clear($this->lockoutKey($request));
    }

    private function lockoutKey(Request $request): string
    {
        return 'lockout:'.$this->throttleKey($request);
    }
}
