<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaFolderController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RecycleBinController;
use App\Http\Controllers\Admin\RoleController;
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
