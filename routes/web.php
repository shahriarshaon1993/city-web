<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Site/Home', [
    'title' => 'Gemcon City',
    'lead' => 'Website foundation — phase 0.1.',
])->name('home');

// Phase 0.2 UI kit reference; never registered outside local.
if (app()->isLocal()) {
    Route::inertia('styleguide', 'Site/Styleguide')->name('styleguide');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
