<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\TableViewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\InvitationController;
use App\Support\Admin\AdminNavigation;
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
