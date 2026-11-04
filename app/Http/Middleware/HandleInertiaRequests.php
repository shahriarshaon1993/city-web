<?php

namespace App\Http\Middleware;

use App\Support\Admin\AdminNavigation;
use App\Support\Consent\ConsentCookie;
use App\Support\Demo\DemoContent;
use App\Support\Forms\FormOptions;
use App\Support\Seo\SeoData;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;
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
     * Route names served by the admin root (admin.blade.php + admin.ts): the
     * screens under /admin and the Fortify auth pages, which Fortify prefixes
     * with `admin` (config/fortify.php).
     *
     * @var list<string>
     */
    private const ADMIN_ROUTES = [
        'admin.*',
        'login',
        'login.*',
        'logout',
        'password.*',
        'verification.*',
        'invitation.*',
    ];

    /**
     * Handle the incoming request.
     *
     * The admin is never server-side rendered (spec/admin.md §10), and nothing
     * is when INERTIA_SSR_ENABLED is off (hosting without Node). disableSsr()
     * replaces the config check, so the flag is folded in here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::disableSsr(
            fn (): bool => ! config('inertia.ssr.enabled') || self::isAdminRequest($request),
        );

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
            'auth' => fn (): ?array => $this->shareAuth($request),
            'navigation' => fn (): array => $request->user() ? AdminNavigation::forUser($request->user()) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Admin `auth` prop: just what the shell needs. `permissions` drives the
     * UI hiding of ARC-005 (the server still authorises every request).
     *
     * @return array{user: array<string, mixed>, permissions: array<int, string>}|null
     */
    private function shareAuth(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => null, // 11.2: avatar_asset_id → MediaImage
                'roles' => $user->getRoleNames()->all(),
                'theme' => $user->theme,
                'table_density' => $user->table_density,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'permissions' => $user->getAllPermissions()->map(fn (Permission $permission): string => $permission->name)->values()->all(),
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
            'forms' => Inertia::once(fn (): array => FormOptions::shared()),
            'seo' => fn (): array => SeoData::raw((string) DemoContent::settings('general')['site_name'])
                ->noindex()
                ->toArray($request),
            'ui' => [
                'motion' => Inertia::once(fn (): array => DemoContent::motion()),
                // CMP-COOKIE-01: the stored choice (null until decided or after a version bump), the
                // banner settings and the analytics IDs (SEO-ANA-001). The browser owns the cookie.
                'consent' => fn (): ?array => ConsentCookie::read($request, DemoContent::cookies()['version']),
                'cookies' => Inertia::once(fn (): array => DemoContent::cookies()),
                'analytics' => Inertia::once(fn (): array => DemoContent::analytics()),
            ],
        ];
    }

    /**
     * Also used by the error pages (App\Support\Errors\ErrorPage): admin errors stay unbranded until 18.2.
     */
    public static function isAdminRequest(Request $request): bool
    {
        // The path check covers 403/404 under /admin, where no route matched.
        return $request->is('admin', 'admin/*') || $request->routeIs(...self::ADMIN_ROUTES);
    }
}
