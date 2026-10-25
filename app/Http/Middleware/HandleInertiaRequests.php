<?php

namespace App\Http\Middleware;

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
        Inertia::disableSsr(fn (): bool => $this->isAdminRequest($request));

        return parent::handle($request, $next);
    }

    /**
     * Pick the public or admin root template by route (ARC-008, SEO-PERF-004).
     */
    public function rootView(Request $request): string
    {
        return $this->isAdminRequest($request) ? 'admin' : $this->rootView;
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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->routeIs(...self::ADMIN_ROUTES);
    }
}
