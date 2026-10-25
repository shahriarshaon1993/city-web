<?php

namespace App\Http\Middleware;

use App\Support\Demo\DemoContent;
use App\Support\Seo\SeoData;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Route names served by the admin root (admin.blade.php + admin.ts).
     * The starter auth/settings routes live here until 10.2 moves the admin under /admin.
     *
     * @var list<string>
     */
    private const ADMIN_ROUTES = [
        'admin.*',
        'dashboard',
        'login',
        'login.*',
        'logout',
        'register',
        'register.*',
        'password.*',
        'user-password.*',
        'two-factor.*',
        'verification.*',
        'profile.*',
        'security.*',
        'appearance.*',
    ];

    /**
     * Handle the incoming request.
     *
     * The admin is never server-side rendered (spec/admin.md §10).
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::disableSsr(fn (): bool => self::isAdminRequest($request));

        return parent::handle($request, $next);
    }

    /**
     * Pick the public or admin root template by route (ARC-008, SEO-PERF-004).
     */
    public function rootView(Request $request): string
    {
        return self::isAdminRequest($request) ? 'admin' : $this->rootView;
    }

    /**
     * Determines the current asset version.
     *
     * The root is part of the version, so an Inertia visit that crosses between
     * the public and admin entries becomes a full page load.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request).':'.$this->rootView($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (! self::isAdminRequest($request)) {
            return [
                ...parent::share($request),
                ...$this->sharePublic($request),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Public shared props (ARC-003, `SiteShared` in types/site.ts). Site and
     * menus are sent once per visit; a page's own `seo` prop replaces the
     * noindex fallback. No `auth` on public pages.
     *
     * @return array<string, mixed>
     */
    private function sharePublic(Request $request): array
    {
        return [
            'site' => Inertia::once(fn (): array => DemoContent::site()),
            'menus' => Inertia::once(fn (): array => DemoContent::menus()),
            'footer' => Inertia::once(fn (): array => DemoContent::footer()),
            'seo' => fn (): array => SeoData::raw((string) DemoContent::settings('general')['site_name'])
                ->noindex()
                ->toArray($request),
            'ui' => [
                'motion' => Inertia::once(fn (): array => DemoContent::motion()),
                // Read from the consent cookie in 7.2.
                'consent' => null,
            ],
        ];
    }

    /**
     * Also used by the error pages (App\Support\Errors\ErrorPage): admin errors stay unbranded until 18.2.
     */
    public static function isAdminRequest(Request $request): bool
    {
        return $request->routeIs(...self::ADMIN_ROUTES);
    }
}
