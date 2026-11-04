<?php

namespace App\Providers;

use App\Listeners\RecordAuthEvents;
use App\Models\User;
use App\Support\Auth\LoginThrottle;
use App\Support\Auth\SystemRole;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\LoginRateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ADM-AUTH-001: 5 failures/minute then a 15-minute lockout, instead of Fortify's plain limiter.
        $this->app->singleton(LoginRateLimiter::class, LoginThrottle::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureAuthorization();
        $this->configureAuthEvents();
    }

    /**
     * ARC-005 / ARC-006: Super Admin passes every Gate check; polymorphic types are
     * stored as aliases. Later phases add their models (project, location, page, post, ...
     * per spec/database.md §11.1).
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole(SystemRole::SuperAdmin->value) ? true : null);

        Relation::enforceMorphMap([
            'user' => User::class,
        ]);
    }

    /**
     * ADM-LOG-002: authentication events go to the activity log.
     */
    protected function configureAuthEvents(): void
    {
        Event::listen(Login::class, [RecordAuthEvents::class, 'onLogin']);
        Event::listen(Failed::class, [RecordAuthEvents::class, 'onFailed']);
        Event::listen(Logout::class, [RecordAuthEvents::class, 'onLogout']);
        Event::listen(PasswordReset::class, [RecordAuthEvents::class, 'onPasswordReset']);
    }

    /**
     * Named rate limiters for public endpoints.
     */
    protected function configureRateLimiting(): void
    {
        // SEO-ANA-006: browser error reports, 10/min/IP.
        RateLimiter::for('client-errors', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // ADM-AUTH-003 (owner, DEC-72: 8+ instead of 12): 8+ characters, mixed case, a number, not in a known breach
        // (free HIBP range API; when it is unreachable the check is skipped and reported).
        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->uncompromised(),
        );
    }
}
