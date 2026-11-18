<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Contracts\PublishableEntity;
use App\Models\Location;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Revision;
use App\Queries\Site\AreaPage;
use App\Queries\Site\ProjectDetail;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * CMS-PUB-005 `/preview/{type}/{id}`: the public template rendered from a revision that is not live.
 * Reached only through a signed link (route middleware `signed`, 60 minutes), so a reviewer needs no
 * account. The page shows the pending revision, or the one in `?revision=`, or the saved entity; it
 * is `noindex,nofollow`, loads no analytics, carries the "Preview — not published" ribbon (shared
 * prop `preview`) and is never cached. Nothing is written.
 */
class PreviewController extends Controller
{
    public function show(Request $request, string $type, int $id): HttpResponse
    {
        $entity = Publishing::find($type, $id);
        $driver = Publishing::for($entity);
        $revision = $this->revision($request, $entity);
        $copy = $driver->hydrate($entity, $revision === null ? $driver->snapshot($entity) : $revision->payload, true);

        $request->attributes->set('preview', [
            'label' => match (true) {
                $revision === null => 'Saved version',
                $revision->kind === Revision::PENDING => 'Pending changes',
                default => 'Revision of '.$revision->created_at?->timezone('Asia/Dhaka')->format('d M Y, H:i'),
            },
            'expires' => Carbon::createFromTimestamp((int) $request->query('expires'))->timezone('Asia/Dhaka')->format('H:i'),
        ]);

        $page = match (true) {
            $copy instanceof Project => app(ProjectController::class)->preview($request, ProjectDetail::preview($copy)),
            $copy instanceof Location && $entity instanceof Location => app(LocationController::class)->preview($request, AreaPage::preview($copy), $entity->slug),
            $copy instanceof Page => app(PageController::class)->preview($request, $copy),
            $copy instanceof Post => app(NewsController::class)->preview($request, $copy),
            default => abort(404),
        };

        $response = $page->toResponse($request);
        $response->headers->add(['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);

        return $response;
    }

    /** The revision the link asks for (404 when it is not this entity's), else the entity's pending one. */
    private function revision(Request $request, Model&PublishableEntity $entity): ?Revision
    {
        if ($request->filled('revision')) {
            return $entity->revisionHistory()->whereKey($request->integer('revision'))->firstOrFail();
        }

        return $entity->pendingRevisionOrNull();
    }
}
