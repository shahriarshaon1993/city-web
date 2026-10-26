<?php

use App\Http\Controllers\Site\ClientErrorController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\ProjectListingController;
use App\Http\Controllers\Site\StyleguideController;
use App\Support\Errors\ErrorPage;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('about', [PageController::class, 'about'])->name('about');
Route::get('about/leadership', [PageController::class, 'leadership'])->name('about.leadership');

// PG-02 / PG-03. Facet slugs use hyphens; the underscore form 301s (owner, 4.1).
Route::get('projects', [ProjectListingController::class, 'index'])->name('site.projects.index');
Route::get('projects/type/{segment}', [ProjectListingController::class, 'segment'])
    ->whereIn('segment', ['residential', 'commercial', 'mixed-use', 'mixed_use', 'land'])
    ->name('site.projects.segment');
Route::get('projects/status/{status}', [ProjectListingController::class, 'status'])
    ->whereIn('status', ['upcoming', 'ongoing', 'ready', 'handed-over', 'handed_over'])
    ->name('site.projects.status');
// PG-04 (4.2). After the facet routes: `type/…` and `status/…` never reach it.
Route::get('projects/{slug}', [ProjectController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.projects.show');

Route::post('internal/client-errors', ClientErrorController::class)
    ->middleware('throttle:client-errors')
    ->name('client-errors.store');

// Phase 0.2/0.3 UI kit reference; never registered outside local.
if (app()->isLocal()) {
    Route::get('styleguide', StyleguideController::class)->name('styleguide');
    Route::get('styleguide/hero/{variant}', [StyleguideController::class, 'hero'])
        ->whereIn('variant', ['image', 'video', 'slider', 'centered'])
        ->name('styleguide.hero');

    // Phase 1.2 error page previews (PG-26), including 500 while APP_DEBUG is on.
    Route::get('styleguide/errors/{status}', function (int $status): never {
        abort($status, headers: $status === 429 ? ['Retry-After' => '60'] : []);
    })->whereIn('status', array_map(strval(...), ErrorPage::STATUSES))->name('styleguide.error');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
