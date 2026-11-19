<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportDownloadController;
use App\Http\Controllers\Admin\FacetPageController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FeaturedProjectController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\JobPostController;
use App\Http\Controllers\Admin\LeadAttachmentController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaFolderController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NotFoundController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PrivacyController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\PublishingController;
use App\Http\Controllers\Admin\RecycleBinController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RobotsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SlugController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\TableViewController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\InvitationController;
use App\Support\Admin\AdminNavigation;
use App\Support\Publishing\Publishing;
use App\Support\RecycleBin\BinTypes;
use Illuminate\Support\Facades\Route;

/*
 * Admin (spec/admin.md §10): everything lives under /admin, including the
 * Fortify auth routes (config/fortify.php prefix). Never SSR'd, noindex.
 */

// ADM-AUTH-006: signed 72-hour invite link, then set a password.
Route::prefix('admin')->group(function () {
    Route::get('invitation/{user}', [InvitationController::class, 'show'])
        ->middleware('signed')
        ->name('invitation.show');
    Route::post('invitation/{user}', [InvitationController::class, 'store'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('invitation.store');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)
        ->middleware('admin.can:dashboard.view')
        ->name('dashboard');

    // ADM-SCR-20: every signed-in user, no permission needed.
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('profile.password');
    Route::patch('profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::delete('profile/sessions', [ProfileController::class, 'destroyOtherSessions'])
        ->middleware('throttle:6,1')
        ->name('profile.sessions.destroy');

    // ADM-SCR-21 Notifications: every signed-in user's own inbox, no permission needed. `feed` is the
    // 60-second poll of the bell (FR-LEAD-008a).
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('feed', [NotificationController::class, 'feed'])->name('feed');
        Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
        Route::delete('read', [NotificationController::class, 'clear'])->name('clear');
        Route::post('{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
        Route::delete('{notification}', [NotificationController::class, 'destroy'])->whereUuid('notification')->name('destroy');
    });

    // ADM-SCR-16 Users & roles. `admin/users` opens for either permission; the controllers authorise each tab.
    Route::middleware('admin.can:users.manage|roles.manage')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('bulk', [UserController::class, 'bulk'])->name('bulk');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('{user}', [UserController::class, 'update'])->name('update');
        Route::post('{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::post('{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::post('{user}/resend', [UserController::class, 'resend'])->middleware('throttle:6,1')->name('resend');
    });

    Route::middleware('admin.can:roles.manage')->prefix('users/roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::patch('{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    // ADM-SCR-17 Activity log (read-only).
    Route::middleware('admin.can:activity.view-all|activity.view-own')->prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('causers', [ActivityLogController::class, 'causers'])->name('causers');
    });

    // DEL-04 Recycle bin. The Policy checks type and role per entry; the screen opens for any bin permission.
    Route::middleware('admin.can:'.implode('|', BinTypes::permissions()))->prefix('recycle-bin')->name('recycle-bin.')->group(function () {
        Route::get('/', [RecycleBinController::class, 'index'])->name('index');
        Route::post('restore', [RecycleBinController::class, 'restoreMany'])->name('restore-many');
        Route::delete('/', [RecycleBinController::class, 'destroyMany'])->name('destroy-many');
        Route::post('{entry}/restore', [RecycleBinController::class, 'restore'])->name('restore');
        Route::delete('{entry}', [RecycleBinController::class, 'destroy'])->name('destroy');
    });

    // ADM-SCR-01 / 02 Projects. The Policy checks each verb (view / write / publish / delete).
    Route::middleware('admin.can:projects.view')->prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::post('bulk', [ProjectController::class, 'bulk'])->name('bulk');
        Route::get('featured', [FeaturedProjectController::class, 'index'])->name('featured.index');
        Route::patch('featured/order', [FeaturedProjectController::class, 'update'])->name('featured.order');
        Route::get('{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::patch('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::patch('{project}/autosave', [ProjectController::class, 'autosave'])->middleware('throttle:30,1')->name('autosave');
        Route::patch('{project}/featured', [ProjectController::class, 'featured'])->name('featured');
        Route::post('{project}/duplicate', [ProjectController::class, 'duplicate'])->name('duplicate');
    });

    // ADM-SCR-06 Pages and the page builder. The Policy checks each verb (builder pages `pages.*`, legal pages `legal.*`).
    Route::middleware('admin.can:pages.view|legal.view')->prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        // CMS-PB-002: blocks of an unsaved editor state, resolved like the public site (600 ms debounce on the client).
        Route::post('preview', [PageController::class, 'preview'])->middleware('throttle:240,1')->name('preview');
        Route::get('{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::patch('{page}', [PageController::class, 'update'])->name('update');
        Route::patch('{page}/autosave', [PageController::class, 'autosave'])->middleware('throttle:30,1')->name('autosave');
        Route::post('{page}/duplicate', [PageController::class, 'duplicate'])->name('duplicate');
        Route::delete('{page}', [PageController::class, 'destroy'])->name('destroy');
    });

    // ADM-SCR-08 News: posts, categories and tags. The Policies check each verb (`news.view` reads, `news.manage` writes).
    Route::middleware('admin.can:news.view')->prefix('news')->name('news.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('create', [PostController::class, 'create'])->name('create');
        Route::post('/', [PostController::class, 'store'])->name('store');

        Route::get('categories', [PostCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [PostCategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/order', [PostCategoryController::class, 'order'])->name('categories.order');
        Route::patch('categories/{category}', [PostCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [PostCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('tags', [TagController::class, 'index'])->name('tags.index');
        Route::patch('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::post('tags/{tag}/merge', [TagController::class, 'merge'])->name('tags.merge');
        Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

        Route::get('{post}/edit', [PostController::class, 'edit'])->name('edit');
        Route::patch('{post}', [PostController::class, 'update'])->name('update');
        Route::patch('{post}/autosave', [PostController::class, 'autosave'])->middleware('throttle:30,1')->name('autosave');
        Route::post('{post}/duplicate', [PostController::class, 'duplicate'])->name('duplicate');
        Route::delete('{post}', [PostController::class, 'destroy'])->name('destroy');
    });

    // ADM-SCR-09 Testimonials, partners, FAQs, team and offices share `social.view` / `social.manage` (the Policies check each verb).
    Route::middleware('admin.can:social.view')->group(function () {
        foreach ([
            'testimonials' => [TestimonialController::class, 'testimonial', true],
            'faqs' => [FaqController::class, 'faq', true],
            'team' => [TeamMemberController::class, 'member', true],
            'offices' => [OfficeController::class, 'office', false],
        ] as $path => [$controller, $parameter, $hasSwitch]) {
            Route::prefix($path)->name("{$path}.")->group(function () use ($controller, $parameter, $hasSwitch) {
                Route::get('/', [$controller, 'index'])->name('index');
                Route::get('create', [$controller, 'create'])->name('create');
                Route::post('/', [$controller, 'store'])->name('store');
                Route::post('bulk', [$controller, 'bulk'])->name('bulk');
                Route::patch('order', [$controller, 'order'])->name('order');
                Route::get("{{$parameter}}/edit", [$controller, 'edit'])->whereNumber($parameter)->name('edit');
                Route::patch("{{$parameter}}", [$controller, 'update'])->whereNumber($parameter)->name('update');

                if ($hasSwitch) {
                    Route::patch("{{$parameter}}/published", [$controller, 'published'])->whereNumber($parameter)->name('published');
                }

                Route::delete("{{$parameter}}", [$controller, 'destroy'])->whereNumber($parameter)->name('destroy');
            });
        }

        // Partners are edited in a dialog: no create / edit pages.
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [PartnerController::class, 'index'])->name('index');
            Route::post('/', [PartnerController::class, 'store'])->name('store');
            Route::post('bulk', [PartnerController::class, 'bulk'])->name('bulk');
            Route::patch('order', [PartnerController::class, 'order'])->name('order');
            Route::patch('{partner}', [PartnerController::class, 'update'])->whereNumber('partner')->name('update');
            Route::patch('{partner}/published', [PartnerController::class, 'published'])->whereNumber('partner')->name('published');
            Route::delete('{partner}', [PartnerController::class, 'destroy'])->whereNumber('partner')->name('destroy');
        });
    });

    // ADM-SCR-03 / 04 / 05 Locations, amenities and facet pages share the `catalog.*` permissions.
    Route::middleware('admin.can:catalog.view')->group(function () {
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/', [LocationController::class, 'index'])->name('index');
            Route::patch('order', [LocationController::class, 'order'])->name('order');
            Route::post('cities', [LocationController::class, 'storeCity'])->name('cities.store');
            Route::patch('cities/{location}', [LocationController::class, 'updateCity'])->name('cities.update');
            Route::get('areas/create', [LocationController::class, 'createArea'])->name('areas.create');
            Route::post('areas', [LocationController::class, 'storeArea'])->name('areas.store');
            Route::get('areas/{location}/edit', [LocationController::class, 'editArea'])->name('areas.edit');
            Route::patch('areas/{location}', [LocationController::class, 'updateArea'])->name('areas.update');
            Route::patch('areas/{location}/autosave', [LocationController::class, 'autosaveArea'])->middleware('throttle:30,1')->name('areas.autosave');
            Route::delete('{location}', [LocationController::class, 'destroy'])->name('destroy');
        });

        Route::get('facet-pages', [FacetPageController::class, 'index'])->name('facets.index');
        Route::get('facet-pages/{facet}/edit', [FacetPageController::class, 'edit'])->name('facets.edit');
        Route::patch('facet-pages/{facet}', [FacetPageController::class, 'update'])->name('facets.update');

        Route::get('amenities', [AmenityController::class, 'index'])->name('amenities.index');
        Route::post('amenities', [AmenityController::class, 'store'])->name('amenities.store');
        Route::post('amenities/bulk', [AmenityController::class, 'bulk'])->name('amenities.bulk');
        Route::patch('amenities/order', [AmenityController::class, 'order'])->name('amenities.order');
        Route::patch('amenities/{amenity}', [AmenityController::class, 'update'])->name('amenities.update');
        Route::delete('amenities/{amenity}', [AmenityController::class, 'destroy'])->name('amenities.destroy');
    });

    // CMS-PUB workflow verbs for every publishable entity: publish, schedule, discard, revisions, preview.
    // The entity's own Policy decides each verb; the group only keeps out users with no content access at all.
    Route::middleware('admin.can:projects.view|catalog.view|pages.view|legal.view|news.view')
        ->prefix('publishing/{type}/{id}')
        ->whereIn('type', Publishing::types())
        ->whereNumber('id')
        ->name('publishing.')
        ->group(function () {
            Route::post('publish', [PublishingController::class, 'publish'])->name('publish');
            Route::post('unpublish', [PublishingController::class, 'unpublish'])->name('unpublish');
            Route::post('discard', [PublishingController::class, 'discard'])->name('discard');
            Route::post('schedule', [PublishingController::class, 'schedule'])->name('schedule');
            Route::post('unschedule', [PublishingController::class, 'unschedule'])->name('unschedule');
            Route::get('revisions', [PublishingController::class, 'revisions'])->name('revisions');
            Route::get('revisions/{revision}/diff', [PublishingController::class, 'diff'])->name('diff');
            Route::post('revisions/{revision}/restore', [PublishingController::class, 'restore'])->name('restore');
            Route::get('preview', [PublishingController::class, 'preview'])->name('preview');
            Route::post('preview-link', [PublishingController::class, 'previewLink'])->middleware('throttle:30,1')->name('preview-link');
        });

    // ADM-SCR-13 media library (CMP-ADM-MEDIA, CMP-ADM-PICKER). The Policy / Form Requests check each action.
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->middleware('admin.can:media.view|media.upload|media.update')->name('index');
        Route::get('browse', [MediaController::class, 'browse'])->name('browse');
        // CMS-MED-003: one file per request.
        Route::post('/', [MediaController::class, 'store'])
            ->middleware(['admin.can:media.upload', 'throttle:120,1'])
            ->name('store');
        Route::post('uploaded', [MediaController::class, 'uploaded'])->middleware('admin.can:media.upload')->name('uploaded');
        Route::post('bulk', [MediaController::class, 'bulk'])->name('bulk');

        Route::post('folders', [MediaFolderController::class, 'store'])->name('folders.store');
        Route::patch('folders/{folder}', [MediaFolderController::class, 'rename'])->name('folders.rename');
        Route::patch('folders/{folder}/move', [MediaFolderController::class, 'move'])->name('folders.move');
        Route::delete('folders/{folder}', [MediaFolderController::class, 'destroy'])->name('folders.destroy');

        Route::get('{asset}', [MediaController::class, 'show'])->name('show');
        Route::patch('{asset}', [MediaController::class, 'update'])->name('update');
        Route::delete('{asset}', [MediaController::class, 'destroy'])->name('destroy');
        Route::post('{asset}/replace', [MediaController::class, 'replace'])->name('replace');
    });

    // ADM-SCR-15 Settings: a tab per group. The page opens for either settings permission; the Policy decides per tab.
    Route::middleware('admin.can:settings.manage|settings.integrations')->prefix('settings')->name('settings.')->group(function () {
        Route::get('{group?}', [SettingsController::class, 'index'])->name('index');
        Route::patch('{group}', [SettingsController::class, 'update'])->name('update');
    });

    // ADM-SCR-12 Menus: a tab per menu (CMS-NAV-001). The page opens read-only with `menus.view`; saving needs `menus.manage` (MenuPolicy).
    Route::middleware('admin.can:menus.view|menus.manage')->group(function () {
        Route::get('menu-link-targets', [MenuController::class, 'targets'])->middleware('throttle:120,1')->name('menus.targets');
        Route::get('menus/{menu?}', [MenuController::class, 'index'])->name('menus.index');
        Route::put('menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
    });

    // ADM-SCR-14 SEO: redirects, 404 monitor, robots.txt. `seo.view` (Viewer) reads, `redirects.manage` /
    // `seo.manage` write; the Policies and Form Requests check each action.
    Route::middleware('admin.can:redirects.manage|seo.view')->group(function () {
        Route::get('redirects', [RedirectController::class, 'index'])->name('redirects.index');
        Route::post('redirects', [RedirectController::class, 'store'])->name('redirects.store');
        Route::post('redirects/import', [RedirectController::class, 'import'])->middleware('throttle:10,1')->name('redirects.import');
        Route::post('redirects/bulk', [RedirectController::class, 'bulk'])->name('redirects.bulk');
        Route::patch('redirects/{redirect}', [RedirectController::class, 'update'])->name('redirects.update');
        Route::patch('redirects/{redirect}/active', [RedirectController::class, 'active'])->name('redirects.active');
        Route::delete('redirects/{redirect}', [RedirectController::class, 'destroy'])->name('redirects.destroy');

        Route::get('404s', [NotFoundController::class, 'index'])->name('not-found.index');
        Route::put('404s/ignore-list', [NotFoundController::class, 'updateIgnoreList'])->name('not-found.ignore-list');
        Route::post('404s/bulk', [NotFoundController::class, 'bulk'])->name('not-found.bulk');
        Route::post('404s/{log}/ignore', [NotFoundController::class, 'ignore'])->name('not-found.ignore');
        Route::delete('404s/{log}', [NotFoundController::class, 'destroy'])->name('not-found.destroy');
    });

    Route::middleware('admin.can:seo.view|seo.manage')->group(function () {
        Route::get('robots', [RobotsController::class, 'edit'])->name('robots.index');
        Route::put('robots', [RobotsController::class, 'update'])->name('robots.update');
    });

    // VAL-04: live slug availability for every slug field.
    Route::get('slugs/check', SlugController::class)->middleware('throttle:60,1')->name('slugs.check');

    // ADM-TBL-010 / 012: saved views and export for every registered table (AdminTables).
    Route::get('export/{table}', [TableController::class, 'export'])->name('tables.export');
    Route::post('table-views', [TableViewController::class, 'store'])->name('table-views.store');
    Route::patch('table-views/{view}', [TableViewController::class, 'update'])->name('table-views.update');
    Route::delete('table-views/{view}', [TableViewController::class, 'destroy'])->name('table-views.destroy');

    // ADM-SCR-11 Leads. The Policy checks every verb per lead (a Sales Executive reaches only their own, AC-LEAD-05).
    Route::middleware('admin.can:leads.view-all|leads.view-assigned')->prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('board', [LeadController::class, 'board'])->name('board');
        Route::get('create', [LeadController::class, 'create'])->name('create');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::post('bulk', [LeadController::class, 'bulk'])->name('bulk');
        Route::get('{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('show');
        Route::patch('{lead}/status', [LeadController::class, 'status'])->whereNumber('lead')->name('status');
        Route::patch('{lead}/assignee', [LeadController::class, 'assign'])->whereNumber('lead')->name('assign');
        Route::post('{lead}/notes', [LeadController::class, 'note'])->whereNumber('lead')->name('notes.store');
        Route::patch('{lead}/follow-up', [LeadController::class, 'followUp'])->whereNumber('lead')->name('follow-up');
        Route::delete('{lead}', [LeadController::class, 'destroy'])->whereNumber('lead')->name('destroy');
        // FR-LEAD-011 (15.1): private landowner documents.
        Route::get('{lead}/attachments/{index}', [LeadAttachmentController::class, 'show'])
            ->whereNumber(['lead', 'index'])
            ->name('attachments.show');
    });

    // ADM-SCR-10 Careers: job posts. The Policy checks each verb (`jobs.view` reads, `jobs.manage` writes).
    Route::middleware('admin.can:jobs.view')->prefix('careers')->name('careers.')->group(function () {
        Route::get('/', [JobPostController::class, 'index'])->name('index');
        Route::get('create', [JobPostController::class, 'create'])->name('create');
        Route::post('/', [JobPostController::class, 'store'])->name('store');
        Route::post('bulk', [JobPostController::class, 'bulk'])->name('bulk');
        Route::get('{job}/edit', [JobPostController::class, 'edit'])->whereNumber('job')->name('edit');
        Route::patch('{job}', [JobPostController::class, 'update'])->whereNumber('job')->name('update');
        Route::patch('{job}/status', [JobPostController::class, 'status'])->whereNumber('job')->name('status');
        Route::get('{job}/preview', [JobPostController::class, 'preview'])->whereNumber('job')->name('preview');
        Route::post('{job}/duplicate', [JobPostController::class, 'duplicate'])->whereNumber('job')->name('duplicate');
        Route::delete('{job}', [JobPostController::class, 'destroy'])->whereNumber('job')->name('destroy');
    });

    // ADM-SCR-10 Applications: personal data, so every verb needs `applications.manage`.
    Route::middleware('admin.can:applications.manage')->prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [JobApplicationController::class, 'index'])->name('index');
        Route::post('bulk', [JobApplicationController::class, 'bulk'])->name('bulk');
        Route::get('{application}', [JobApplicationController::class, 'show'])->whereNumber('application')->name('show');
        Route::patch('{application}/status', [JobApplicationController::class, 'status'])->whereNumber('application')->name('status');
        Route::patch('{application}/notes', [JobApplicationController::class, 'notes'])->whereNumber('application')->name('notes');
        // FR-CAR-005: the CV leaves the private disk only through this route.
        Route::get('{application}/cv', [JobApplicationController::class, 'cv'])->whereNumber('application')->name('cv');
        Route::delete('{application}', [JobApplicationController::class, 'destroy'])->whereNumber('application')->name('destroy');
    });

    // ADM-IO-001 Import wizard. Each run belongs to the person who started it (ImportRunPolicy); which
    // kinds of data a person may import is `ImportType::canBeUsedBy`.
    Route::middleware('admin.can:import.content|import.leads')->prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'store'])->middleware('throttle:20,1')->name('store');
        Route::get('templates/{type}', [ImportController::class, 'template'])->name('template');
        Route::get('runs/{run}', [ImportController::class, 'show'])->whereNumber('run')->name('runs.show');
        Route::put('runs/{run}/mapping', [ImportController::class, 'mapping'])->whereNumber('run')->middleware('throttle:120,1')->name('runs.mapping');
        Route::post('runs/{run}/start', [ImportController::class, 'start'])->whereNumber('run')->name('runs.start');
        Route::get('runs/{run}/problems', [ImportController::class, 'errors'])->whereNumber('run')->name('runs.errors');
        Route::delete('runs/{run}', [ImportController::class, 'destroy'])->whereNumber('run')->name('runs.destroy');
    });

    // ADM-TBL-012: the signed 24-hour link of a queued export (the signature is a relative one).
    Route::get('exports/{export}/download', ExportDownloadController::class)
        ->whereNumber('export')
        ->middleware('signed:relative')
        ->name('exports.download');

    // NFR-PRIV-03 Personal data: find, export, erase everything held for a phone and/or an email.
    Route::middleware('admin.can:privacy.manage')->prefix('privacy')->name('privacy.')->group(function () {
        Route::get('/', [PrivacyController::class, 'index'])->name('index');
        Route::post('search', [PrivacyController::class, 'search'])->middleware('throttle:30,1')->name('search');
        Route::post('export', [PrivacyController::class, 'export'])->middleware('throttle:10,1')->name('export');
        Route::delete('/', [PrivacyController::class, 'erase'])->middleware('throttle:10,1')->name('erase');
    });

    // ADM-SCR-12 Subscribers.
    Route::middleware('admin.can:subscribers.manage')->prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/', [SubscriberController::class, 'index'])->name('index');
        Route::post('bulk', [SubscriberController::class, 'bulk'])->name('bulk');
        Route::post('{subscriber}/unsubscribe', [SubscriberController::class, 'unsubscribe'])->whereNumber('subscriber')->name('unsubscribe');
        Route::delete('{subscriber}', [SubscriberController::class, 'destroy'])->whereNumber('subscriber')->name('destroy');
        Route::delete('{subscriber}/erase', [SubscriberController::class, 'erase'])->whereNumber('subscriber')->name('erase');
    });

    // Sidebar items whose screens come in later phases (registry: AdminNavigation).
    foreach (AdminNavigation::items() as $item) {
        if ($item->placeholder) {
            Route::get(substr($item->path, strlen('admin/')), PlaceholderController::class)
                ->middleware('admin.can:'.implode('|', $item->permissions))
                ->defaults('item', $item->key)
                ->name("{$item->key}.index");
        }
    }
});
