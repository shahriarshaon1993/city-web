<?php

namespace App\Http\Middleware;

use App\Actions\Redirects\RecordRedirectHit;
use App\Support\Redirects\RedirectPath;
use App\Support\Redirects\RuleSet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * SEO-TEC-008: the redirect manager's rules, evaluated before routing (and before `NormalizeUrl`,
 * so `/Old/` needs one hop, not two). GET/HEAD only; the admin, `/internal` and `/up` are never
 * touched. A 301/302 keeps the visitor's query string unless the destination has its own; a 410
 * rule answers Gone through the branded error page (SEO-TEC-007). The hit is counted after the
 * response, and a failing rule source never takes the site down.
 */
class ApplyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || $request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $path = RedirectPath::normalise($request->getPathInfo());

        if (RedirectPath::isProtected($path)) {
            return $next($request);
        }

        try {
            $rules = RuleSet::current();
            $rule = $rules->match($path);
        } catch (Throwable $exception) {
            report($exception);

            return $next($request);
        }

        if ($rule === null) {
            return $next($request);
        }

        $redirectId = $rule['id'];
        defer(fn () => app(RecordRedirectHit::class)->handle((int) $redirectId), always: true);

        $destination = $rules->destination($rule, $path);

        if ($destination === null) {
            abort(410);
        }

        $query = (string) $request->server->get('QUERY_STRING', '');

        if ($query !== '' && ! str_contains($destination, '?')) {
            $destination .= '?'.$query;
        }

        return redirect()->to($destination, $rule['code']);
    }
}
