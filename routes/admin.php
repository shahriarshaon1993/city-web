<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacetPageController;
use App\Http\Controllers\Admin\FeaturedProjectController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaFolderController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NotFoundController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RecycleBinController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RobotsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SlugController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\TableViewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\InvitationController;
use App\Support\Admin\AdminNavigation;
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
        Route::post('{project}/publish', [ProjectController::class, 'publish'])->name('publish');
        Route::post('{project}/unpublish', [ProjectController::class, 'unpublish'])->name('unpublish');
        Route::patch('{project}/featured', [ProjectController::class, 'featured'])->name('featured');
        Route::post('{project}/duplicate', [ProjectController::class, 'duplicate'])->name('duplicate');
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
