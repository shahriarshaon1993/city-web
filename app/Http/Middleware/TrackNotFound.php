<?php

namespace App\Http\Middleware;

use App\Actions\Monitoring\RecordNotFound;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * SEO-TEC-009: a public GET/HEAD that ends in a 404 is counted in the 404 monitor. The admin and
 * `/internal` are left out. The write happens after the response (`defer`) and can never turn a
 * 404 into a 500.
 */
class TrackNotFound
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404 && $request->isMethodSafe() && ! $request->is('admin', 'admin/*', 'internal/*')) {
            $path = $request->getPathInfo();
            $referrer = $request->headers->get('referer');

            defer(function () use ($path, $referrer): void {
                try {
                    app(RecordNotFound::class)->handle($path, $referrer);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }, always: true); // `defer` skips 4xx responses unless told otherwise
        }

        return $response;
    }
}
