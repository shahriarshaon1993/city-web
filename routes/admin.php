<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProfileController;
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
