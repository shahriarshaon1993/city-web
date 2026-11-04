<?php

namespace App\Http\Middleware;

use App\Support\Seo\SeoData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Outside production (or with Settings → SEO "noindex whole site") every
 * response sends `X-Robots-Tag: noindex, nofollow` (SEO-TEC-010). The admin
 * (`/admin`, auth and invitation pages included) always does (spec/admin.md §10).
 */
class AddNoindexHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('admin', 'admin/*') || SeoData::siteNoindex()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
