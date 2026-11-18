<?php

namespace App\Providers;

use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Listeners\ForwardLeadToWebhook;
use App\Listeners\NotifyAboutLead;
use App\Listeners\RecordAuthEvents;
use App\Models\Amenity;
use App\Models\FacetPage;
use App\Models\Lead;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\Menu;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectNearbyPlace;
use App\Models\ProjectProgressUpdate;
use App\Models\ProjectUnitType;
use App\Models\Redirect;
use App\Models\SeoMeta;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\Auth\LoginThrottle;
use App\Support\Auth\SystemRole;
use App\Support\Catalog\CatalogCache;
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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

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
        $this->configureCatalogCache();
        $this->configureLeadEvents();
    }

    /**
     * FR-LEAD-008 / FR-LEAD-012: a lead created or changed raises a notification, the optional emails
     * and the optional webhook. Explicit, because one listener class answers two events.
     */
    protected function configureLeadEvents(): void
    {
        Event::listen(LeadCreated::class, [NotifyAboutLead::class, 'onCreated']);
        Event::listen(LeadUpdated::class, [NotifyAboutLead::class, 'onUpdated']);
        Event::listen(LeadCreated::class, [ForwardLeadToWebhook::class, 'onCreated']);
        Event::listen(LeadUpdated::class, [ForwardLeadToWebhook::class, 'onUpdated']);
    }

    /**
     * ARC-005 / ARC-006: Super Admin passes every Gate check; polymorphic types are
     * stored as aliases. Later phases add their models (project, location, page, post, ...
     * per spec/database.md §11.1).
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->hasRole(SystemRole::SuperAdmin->value) ? true : null);

        Gate::policy(Role::class, RolePolicy::class);

        Relation::enforceMorphMap([
            'user' => User::class,
            'media_asset' => MediaAsset::class,
            'media_folder' => MediaFolder::class,
            'setting' => Setting::class,
            'menu' => Menu::class,
            'redirect' => Redirect::class,
            'project' => Project::class,
            'unit_type' => ProjectUnitType::class,
            'progress_update' => ProjectProgressUpdate::class,
            'location' => Location::class,
            'amenity' => Amenity::class,
            'facet_page' => FacetPage::class,
            'lead' => Lead::class,
            'newsletter_subscriber' => NewsletterSubscriber::class,
            'page' => Page::class,
            'post' => Post::class,
            'post_category' => PostCategory::class,
            'tag' => Tag::class,
        ]);
    }

    /**
     * CMS-CACHE-002: any change to what a public project or area page shows raises the catalogue
     * version, so cached cards, lists and pages are rebuilt on the next request.
     */
    protected function configureCatalogCache(): void
    {
        $models = [
            Project::class, ProjectUnitType::class, ProjectProgressUpdate::class, ProjectNearbyPlace::class,
            ProjectDocument::class, Location::class, Amenity::class, FacetPage::class, SeoMeta::class,
            MediaAsset::class, Media::class,
        ];

        foreach ($models as $model) {
            foreach (['saved', 'deleted', 'restored'] as $event) {
                $model::registerModelEvent($event, static fn (): null => CatalogCache::bust());
            }
        }
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

        // FR-LEAD-003c / AC-LEAD-03: 5 submissions per 10 minutes per IP per form; the 6th is a 429 with
        // Retry-After. The newsletter counts as its own form. The 20-a-day limit per phone is in SubmitLead.
        RateLimiter::for('lead-forms', fn (Request $request): Limit => Limit::perMinutes(10, 5)
            ->by($request->ip().'|'.($request->route('form') ?? 'newsletter')));
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
