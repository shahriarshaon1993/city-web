<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One 301 to the clean URL (SEO-TEC-002, SEO-TEC-005): no `index.php`, no
 * duplicate or trailing slashes, lowercase path, no `?page=1`. GET/HEAD only,
 * so form posts are never redirected. Global middleware: runs before routing,
 * so unknown paths are normalised before they 404.
 */
class NormalizeUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $target = $this->cleanTarget($request);

        if ($target !== null) {
            return redirect()->to($target, 301);
        }

        return $next($request);
    }

    /**
     * The absolute clean URL, or null when the request is already clean.
     */
    private function cleanTarget(Request $request): ?string
    {
        $baseUrl = $request->getBaseUrl();
        $hasIndexPhp = str_ends_with($baseUrl, '/index.php');
        $prefix = $hasIndexPhp ? substr($baseUrl, 0, -strlen('/index.php')) : $baseUrl;

        $path = $request->getPathInfo();
        $cleanPath = preg_replace('#/{2,}#', '/', $path) ?? $path;
        $cleanPath = preg_replace('#(/index\.php)+$#i', '', $cleanPath) ?? $cleanPath;
        $cleanPath = $cleanPath === '/' ? '/' : rtrim($cleanPath, '/');
        $cleanPath = $cleanPath === '' ? '/' : $cleanPath;

        // Decode before checking so percent-escapes (%C3) never count as uppercase.
        if (preg_match('/[A-Z]/', rawurldecode($cleanPath)) === 1) {
            $cleanPath = strtolower($cleanPath);
        }

        $query = (string) $request->server->get('QUERY_STRING', '');
        $cleanQuery = implode('&', array_filter(
            explode('&', $query),
            fn (string $pair): bool => $pair !== '' && ! $this->isFirstPage($pair),
        ));

        if (! $hasIndexPhp && $cleanPath === $path && $cleanQuery === $query) {
            return null;
        }

        return $request->getSchemeAndHttpHost().$prefix.$cleanPath.($cleanQuery === '' ? '' : '?'.$cleanQuery);
    }

    private function isFirstPage(string $pair): bool
    {
        [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');

        return rawurldecode($key) === 'page' && $value === '1';
    }
}
