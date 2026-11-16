<?php

namespace App\Actions\Redirects;

use App\Actions\Activity\LogActivity;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Redirects\RuleSet;
use Illuminate\Validation\ValidationException;

/**
 * The inline Active switch of the redirects list (CRUD-01). Switching a rule on re-checks that it
 * would not close a circle with rules added while it was off.
 */
final class SetRedirectActive
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException when switching it on would create a loop
     */
    public function handle(User $actor, Redirect $redirect, bool $active): Redirect
    {
        if ($redirect->is_active === $active) {
            return $redirect;
        }

        if ($active) {
            $trace = RuleSet::fromDatabase()->withRule($redirect)->trace($redirect->source);

            if ($trace['loop']) {
                throw ValidationException::withMessages([
                    'redirect' => 'It would send visitors in circles ('.implode(' → ', $trace['chain']).'), so it stays off.',
                ]);
            }
        }

        $redirect->forceFill(['is_active' => $active])->save();

        $this->logActivity->handle('redirects', 'updated', 'Redirect '.($active ? 'switched on' : 'switched off').": {$redirect->sourceLabel()}", $actor, $redirect, [
            'source' => $redirect->sourceLabel(),
            'active' => $active,
        ]);

        return $redirect;
    }
}
