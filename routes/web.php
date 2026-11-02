<?php

use App\Http\Controllers\Site\ClientErrorController;
use App\Http\Controllers\Site\FormController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocationController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\ProjectListingController;
use App\Http\Controllers\Site\StyleguideController;
use App\Http\Controllers\Site\ThankYouController;
use App\Support\Errors\ErrorPage;
use App\Support\Forms\FormType;
use App\Support\Pages\ReservedSlugs;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
// PG-07…11, PG-23/24 (6.3). The custom-page catch-all (PG-25) is registered last.
Route::get('about', [PageController::class, 'about'])->name('about');
Route::get('about/leadership', [PageController::class, 'leadership'])->name('about.leadership');
Route::get('services', [PageController::class, 'services'])->name('services');
Route::get('landowner', [PageController::class, 'landowner'])->name('landowner');
Route::get('nrb', [PageController::class, 'nrb'])->name('nrb');
Route::get('privacy-policy', [PageController::class, 'privacy'])->name('privacy-policy');
Route::get('terms', [PageController::class, 'terms'])->name('terms');

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

// PG-05 / PG-06 (5.1). Area × segment pages exist only with ≥ 2 projects (FR-LOC-004).
Route::get('locations', [LocationController::class, 'index'])->name('site.locations.index');
Route::get('locations/{area}', [LocationController::class, 'show'])
    ->where('area', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.locations.show');
Route::get('locations/{area}/{segment}', [LocationController::class, 'segment'])
    ->where('area', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->whereIn('segment', ['residential', 'commercial', 'mixed-use', 'mixed_use', 'land'])
    ->name('site.locations.segment');

// FR-LEAD workflow (7.1): the seven lead forms post here; PG-22 is a plain
// confirmation until 7.2.
Route::post('forms/{form}', [FormController::class, 'store'])
    ->whereIn('form', FormType::slugs())
    ->name('site.forms.store');
Route::get('thank-you/{form}', ThankYouController::class)
    ->whereIn('form', FormType::slugs())
    ->name('site.thank-you');

Route::post('internal/client-errors', ClientErrorController::class)
    ->middleware('throttle:client-errors')
    ->name('client-errors.store');

// Phase 0.2/0.3 UI kit reference; never registered outside local.
if (app()->isLocal()) {
    Route::get('styleguide', StyleguideController::class)->name('styleguide');
    Route::get('styleguide/hero/{variant}', [StyleguideController::class, 'hero'])
        ->whereIn('variant', ['image', 'video', 'slider', 'centered'])
        ->name('styleguide.hero');

    Route::get('styleguide/blocks', [StyleguideController::class, 'blocks'])->name('styleguide.blocks');
    Route::get('styleguide/forms', [StyleguideController::class, 'forms'])->name('styleguide.forms');

    // Phase 1.2 error page previews (PG-26), including 500 while APP_DEBUG is on.
    Route::get('styleguide/errors/{status}', function (int $status): never {
        abort($status, headers: $status === 429 ? ['Retry-After' => '60'] : []);
    })->whereIn('status', array_map(strval(...), ErrorPage::STATUSES))->name('styleguide.error');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// PG-25: any custom page. Last, so every fixed route wins; reserved slugs
// (spec/pages.md §5.2 + the app's own paths) never match.
Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', ReservedSlugs::routePattern())
    ->name('site.pages.show');
