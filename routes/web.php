<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('about', [PageController::class, 'about'])->name('about');

// Phase 0.2/0.3 UI kit reference; never registered outside local.
if (app()->isLocal()) {
    Route::get('styleguide', StyleguideController::class)->name('styleguide');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
