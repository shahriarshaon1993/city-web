<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * CMS-PB-002: the page the builder's preview iframe shows. It is the public layout around an empty block
 * list; the admin pushes the blocks of its unsaved state into it (postMessage, `Site/BuilderPreview`) so
 * an edit shows within a second without a reload. Signed-in editors only; never indexed, cached or
 * counted by analytics (the `preview` attribute switches analytics off, its null value hides the ribbon).
 */
class BuilderFrameController extends Controller
{
    public function __invoke(Request $request): HttpResponse
    {
        Gate::authorize('viewAny', Page::class);

        $request->attributes->set('preview', null);

        $response = Inertia::render('Site/BuilderPreview', ['blocks' => [], 'starts_with_hero' => false])->toResponse($request);
        $response->headers->add(['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);

        return $response;
    }
}
