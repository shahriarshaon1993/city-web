<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `admin.can:a|b` — 403 unless the user holds at least one of the listed
 * permissions (Super Admin passes through Gate::before). Laravel's `can:`
 * middleware reads extra arguments as model arguments, not as alternatives.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        abort_unless($request->user()?->canAny(explode('|', $permissions)), 403);

        return $next($request);
    }
}
