<?php

namespace App\Actions\Redirects;

use App\Actions\Activity\LogActivity;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Redirects\RedirectPath;
use App\Support\Redirects\RuleSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SEO-TEC-008: creates or updates one redirect. The Form Request has checked the shape of the
 * values; here the rule set decides what a single field cannot:
 *
 *  - the 2,000-rule cap;
 *  - a loop (A → B while B → A) is refused with the path it would take;
 *  - a chain is collapsed instead of stored: A → B while B → C saves A → C, and rules that
 *    already pointed at A are re-pointed to the new destination (SEO-TEC-003 style).
 *
 * An inactive rule is stored as is; `SetRedirectActive` runs the loop check when it is switched on.
 */
final class SaveRedirect
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated `source`, `code`, `target` and `is_active` (see RedirectRules)
     * @param  RuleSet|null  $rules  a prepared rule set, so an import does not reload the table per row
     * @return array{redirect: Redirect, shortened: int, changed: bool}
     *
     * @throws ValidationException when the cap is reached or the rule would loop
     */
    public function handle(User $actor, array $data, ?Redirect $redirect = null, ?RuleSet $rules = null, bool $log = true): array
    {
        $parsed = RedirectPath::parseSource((string) $data['source'])
            ?? throw ValidationException::withMessages(['source' => 'Enter an address on this site, like /old-page.']);

        $code = (int) $data['code'];
        $target = $code === 410 ? null : trim((string) ($data['target'] ?? ''));
        $active = (bool) ($data['is_active'] ?? true);

        return DB::transaction(function () use ($actor, $parsed, $code, $target, $active, $redirect, $rules, $log): array {
            if ($redirect === null && Redirect::query()->count() >= Redirect::MAX_RULES) {
                throw ValidationException::withMessages([
                    'source' => 'You have reached the limit of '.number_format(Redirect::MAX_RULES).' redirects. Delete rules you no longer need.',
                ]);
            }

            $shortened = 0;

            if ($active && $target !== null) {
                [$target, $shortened] = $this->checked($parsed, $code, $target, $redirect, $rules ?? RuleSet::fromDatabase());
            }

            $attributes = [
                'source' => $parsed['source'],
                'is_prefix' => $parsed['is_prefix'],
                'target' => $target,
                'code' => $code,
                'is_active' => $active,
            ];

            $event = $redirect === null ? 'created' : 'updated';
            $redirect ??= new Redirect(['created_by' => $actor->id]);
            $redirect->fill($attributes);
            $changed = $redirect->isDirty();

            if ($changed) {
                $redirect->save();
            }

            if ($active && $target !== null && ! $parsed['is_prefix']) {
                $shortened += $this->repointInbound($redirect, $target);
            }

            if ($log && $changed) {
                $this->logActivity->handle('redirects', $event, "Redirect {$event}: {$redirect->sourceLabel()}", $actor, $redirect, [
                    'source' => $redirect->sourceLabel(),
                    'target' => $redirect->target,
                    'code' => $redirect->code,
                    'active' => $redirect->is_active,
                    'shortened' => $shortened,
                ]);
            }

            return ['redirect' => $redirect, 'shortened' => $shortened, 'changed' => $changed || $shortened > 0];
        });
    }

    /**
     * Refuses a loop and collapses a chain.
     *
     * @param  array{source: string, is_prefix: bool}  $parsed
     * @return array{0: string, 1: int} the destination to store and how many hops it saved
     */
    private function checked(array $parsed, int $code, string $target, ?Redirect $redirect, RuleSet $rules): array
    {
        $others = $rules->without($redirect?->id);
        $candidate = ['id' => $redirect?->id, 'source' => $parsed['source'], 'is_prefix' => $parsed['is_prefix'], 'target' => $target, 'code' => $code];

        $trace = $others->withRule($candidate)->trace($parsed['source']);

        if ($trace['loop']) {
            throw ValidationException::withMessages([
                'target' => 'This would send visitors in circles: '.implode(' → ', $trace['chain']).'. Change one of the redirects first.',
            ]);
        }

        $own = str_ends_with($target, '/*') ? null : RedirectPath::ownPath($target);

        if ($own === null) {
            return [$target, 0];
        }

        $onward = $others->trace($own);

        if ($onward['hops'] === 0 || $onward['gone']) {
            return [$target, 0];
        }

        return [$onward['final'], 1];
    }

    /**
     * Rules that sent visitors to this rule's old address now go straight to its destination.
     */
    private function repointInbound(Redirect $redirect, string $target): int
    {
        return Redirect::query()
            ->where('target', $redirect->source)
            ->whereKeyNot($redirect->id)
            ->where('code', '!=', 410)
            ->get()
            ->each(fn (Redirect $inbound) => $inbound->update(['target' => $target]))
            ->count();
    }
}
