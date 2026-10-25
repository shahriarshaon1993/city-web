<?php

namespace App\Support\Errors;

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Demo\DemoContent;
use App\Support\Seo\SeoData;
use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * PG-26 / LAY-ERROR: branded error pages through Inertia's exception handler
 * (registered in bootstrap/app.php). The status code is kept (SEO-TEC-007);
 * 500/503 render without shared props and with a logo-only header, so a
 * failing menu or settings source cannot break them. Admin and JSON
 * requests keep Laravel's default responses.
 */
final class ErrorPage
{
    /** @var list<int> */
    public const array STATUSES = [404, 410, 419, 429, 500, 503];

    /** @var list<int> */
    private const array MINIMAL = [500, 503];

    /** SEO-TEC-007: `Retry-After: 3600` when maintenance mode sets none. */
    private const int MAINTENANCE_RETRY_SECONDS = 3600;

    /**
     * The branded response, or null to keep Laravel's own.
     */
    public static function render(ExceptionResponse $exception): ?Response
    {
        $status = $exception->statusCode();
        $request = $exception->request;

        if (! in_array($status, self::STATUSES, true) || ! self::isPublicPage($request)) {
            return null;
        }

        // Debug mode keeps the exception page; the local preview route shows the branded one.
        if ($status === 500 && config('app.debug') && ! $request->routeIs('styleguide.error')) {
            return null;
        }

        $original = $exception->response;
        $minimal = in_array($status, self::MINIMAL, true);

        try {
            $exception->render('Site/Error', self::props($status, $request, $original, $minimal));

            if (! $minimal) {
                $exception->withSharedData();
            }

            $response = $exception->toResponse($request);
        } catch (Throwable) {
            return null;
        }

        $retryAfter = $original->headers->get('Retry-After')
            ?? ($status === 503 ? (string) self::MAINTENANCE_RETRY_SECONDS : null);

        if ($retryAfter !== null) {
            $response->headers->set('Retry-After', $retryAfter);
        }

        // SEO-TEC-007: 5xx is never cached.
        if ($status >= 500) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    private static function isPublicPage(Request $request): bool
    {
        if (HandleInertiaRequests::isAdminRequest($request)) {
            return false;
        }

        return $request->header('X-Inertia') !== null || ! $request->expectsJson();
    }

    /**
     * `Site/Error` props (ErrorPageProps in resources/js/types/site.ts).
     *
     * @return array<string, mixed>
     */
    private static function props(int $status, Request $request, Response $original, bool $minimal): array
    {
        $copy = DemoContent::errorPage($status);

        $props = [
            'status' => $status,
            'title' => $copy['title'],
            'message' => $copy['message'],
            'retry_text' => $status === 503 ? self::retryText($original) : null,
            // PG-19 site search arrives in 8.2; the form already submits there.
            'search' => $status === 404 ? ['action' => '/search', 'name' => 'q', 'min_length' => 2] : null,
            'popular' => $status === 404 ? [
                'heading' => $copy['popular_heading'],
                'projects' => array_slice(DemoContent::projectCards(featuredOnly: true), 0, 3),
            ] : null,
            'minimal' => $minimal,
            'seo' => SeoData::page($copy['title'])->noindex()->toArray($request),
        ];

        // The logo-only header still needs the site name and logos.
        if ($minimal) {
            $props['site'] = DemoContent::site();
        }

        return $props;
    }

    /**
     * "Expected back …" from the maintenance Retry-After (seconds or HTTP date).
     */
    private static function retryText(Response $original): string
    {
        $header = $original->headers->get('Retry-After');
        $seconds = match (true) {
            $header === null => self::MAINTENANCE_RETRY_SECONDS,
            ctype_digit($header) => (int) $header,
            default => max(0, (strtotime($header) ?: time()) - time()),
        };

        $minutes = (int) ceil($seconds / 60);

        return match (true) {
            $minutes <= 1 => 'We expect to be back within a minute.',
            $minutes < 60 => "We expect to be back within about {$minutes} minutes.",
            $minutes < 120 => 'We expect to be back within about an hour.',
            default => 'We expect to be back within about '.intdiv($minutes, 60).' hours.',
        };
    }
}
