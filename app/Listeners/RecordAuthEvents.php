<?php

namespace App\Listeners;

use App\Actions\Auth\RecordAuthEvent;
use App\Actions\Auth\RecordLogin;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

/**
 * Wires Laravel's auth events to the audit log (ADM-LOG-002). Registered
 * explicitly in AppServiceProvider.
 */
final class RecordAuthEvents
{
    public function __construct(
        private readonly Request $request,
        private readonly RecordLogin $recordLogin,
        private readonly RecordAuthEvent $record,
    ) {}

    public function onLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->recordLogin->handle($event->user, $this->request);
        }
    }

    public function onFailed(Failed $event): void
    {
        $this->record->handle(
            'login_failed',
            'Failed sign-in attempt',
            $event->user instanceof User ? $event->user : null,
            $this->request,
            ['email' => mb_strtolower((string) ($event->credentials['email'] ?? ''))],
        );
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->record->handle('logout', 'Signed out', $event->user, $this->request);
        }
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->record->handle('password_changed', 'Password changed (reset link)', $event->user, $this->request);
        }
    }
}
