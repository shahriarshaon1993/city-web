<?php

use App\Http\Controllers\Site\BuilderFrameController;
use App\Http\Controllers\Site\CareerController;
use App\Http\Controllers\Site\ClientErrorController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\FormController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LocationController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\NewsFeedController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PreviewController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\ProjectDocumentController;
use App\Http\Controllers\Site\ProjectListingController;
use App\Http\Controllers\Site\RobotsTxtController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\ShortlistController;
use App\Http\Controllers\Site\StyleguideController;
use App\Http\Controllers\Site\ThankYouController;
use App\Support\Errors\ErrorPage;
use App\Support\Forms\FormType;
use App\Support\Pages\ReservedSlugs;
use App\Support\Publishing\Publishing;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

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

// PG-12…14 (8.1). Category and tag archives are two segments, so a post slug never collides.
Route::get('news', [NewsController::class, 'index'])->name('site.news.index');
// FR-NEWS-008: the RSS feed (the dot keeps it out of the post-slug route below).
Route::get('news/feed.xml', NewsFeedController::class)->name('site.news.feed');
Route::get('news/category/{category}', [NewsController::class, 'category'])
    ->where('category', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.news.category');
Route::get('news/tag/{tag}', [NewsController::class, 'tag'])
    ->where('tag', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.news.tag');
Route::get('news/{slug}', [NewsController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.news.show');

// PG-15 / PG-16 (8.2). Applications and the general CV drop post to one route; 17.1 adds the honeypot + minimum fill time (FR-CAR-002).
Route::get('careers', [CareerController::class, 'index'])->name('site.careers.index');
Route::post('careers/apply', [CareerController::class, 'apply'])
    ->middleware(['throttle:6,1', ProtectAgainstSpam::class])
    ->name('site.careers.apply');
Route::get('careers/{slug}', [CareerController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('site.careers.show');

// PG-18 / PG-19 (8.2).
Route::get('faq', FaqController::class)->name('site.faq');
Route::get('search', SearchController::class)->name('site.search');

// PG-20 / PG-21 (8.3): IDs come from the visitor's browser storage.
Route::get('shortlist', [ShortlistController::class, 'index'])->name('site.shortlist');
Route::get('compare', [ShortlistController::class, 'compare'])->name('site.compare');

// PG-17 (7.2).
Route::get('contact', ContactController::class)->name('site.contact');

// CMS-PUB-005 (14.1): signed, 60-minute preview of a pending revision for reviewers without an account.
Route::get('preview/{type}/{id}', [PreviewController::class, 'show'])
    ->whereIn('type', Publishing::types())
    ->whereNumber('id')
    ->middleware('signed')
    ->name('preview.show');

// CMS-PB-002 (16.2): the public layout inside the page builder's preview iframe, for signed-in editors only.
Route::get('preview/builder', BuilderFrameController::class)
    ->middleware(['auth', 'verified'])
    ->name('preview.builder');

// SEO-TEC-010 (12.3): dynamic, never a static file.
Route::get('robots.txt', RobotsTxtController::class)->name('site.robots');

// FR-LEAD workflow (7.1, 7.2): the seven lead forms post here and land on PG-22.
// FR-LEAD-003: rate limit (AC-LEAD-03), then honeypot + minimum fill time (AC-LEAD-02).
Route::post('forms/{form}', [FormController::class, 'store'])
    ->whereIn('form', FormType::slugs())
    ->middleware(['throttle:lead-forms', ProtectAgainstSpam::class])
    ->name('site.forms.store');
Route::get('thank-you/{form}', ThankYouController::class)
    ->whereIn('form', FormType::slugs())
    ->name('site.thank-you');

// Footer / article newsletter sign-up (7.2); the form shows its own success state.
Route::post('newsletter', [NewsletterController::class, 'store'])
    ->middleware(['throttle:lead-forms', ProtectAgainstSpam::class])
    ->name('site.newsletter.store');
// Links in the double opt-in mail and in every newsletter: the token is the proof (DM-SUBS).
Route::get('newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('site.newsletter.confirm');
Route::get('newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('site.newsletter.unsubscribe');

// AC-PROJ-05: gated brochure, signed URL valid 10 minutes (CreateBrochureLink).
Route::get('projects/{slug}/documents/{document}/download', [ProjectDocumentController::class, 'download'])
    ->whereNumber('document')
    ->middleware('signed')
    ->name('site.projects.documents.download');

Route::post('internal/client-errors', ClientErrorController::class)
    ->middleware('throttle:client-errors')
    ->name('client-errors.store');

// Phase 0.2/0.3 UI kit reference; never registered outside local.
if (app()->isLocal()) {
    Route::get('styleguide', StyleguideController::class)->name('styleguide');
    Route::get('styleguide/forms', [StyleguideController::class, 'forms'])->name('styleguide.forms');

    // Phase 1.2 error page previews (PG-26), including 500 while APP_DEBUG is on.
    Route::get('styleguide/errors/{status}', function (int $status): never {
        abort($status, headers: $status === 429 ? ['Retry-After' => '60'] : []);
    })->whereIn('status', array_map(strval(...), ErrorPage::STATUSES))->name('styleguide.error');
}

// Admin under /admin (10.2): auth, invitation, profile and the sidebar screens.
require __DIR__.'/admin.php';

// PG-25: any custom page. Last, so every fixed route wins; reserved slugs
// (spec/pages.md §5.2 + the app's own paths) never match.
Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', ReservedSlugs::routePattern())
    ->name('site.pages.show');
