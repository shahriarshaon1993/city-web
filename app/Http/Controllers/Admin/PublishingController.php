<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Publishing\DiscardPendingChanges;
use App\Actions\Publishing\PublishEntity;
use App\Actions\Publishing\RestoreRevision;
use App\Actions\Publishing\ScheduleEntity;
use App\Actions\Publishing\UnpublishEntity;
use App\Actions\Publishing\UnscheduleEntity;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchedulePublishRequest;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Support\Publishing\PreviewLink;
use App\Support\Publishing\Publishing;
use App\Support\Publishing\RevisionDiff;
use App\Support\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The workflow verbs of CMS-PUB for every publishable entity (`/admin/publishing/{type}/{id}/…`):
 * publish, unpublish, discard, schedule, unschedule, revisions, diff, restore and preview links. The
 * entity's own Policy decides (`publish` for the verbs that change what is public, `update` for the
 * rest); the Actions do the work (DEC-32). Save and autosave live in each entity's controller because
 * they validate the entity's own form.
 */
class PublishingController extends Controller
{
    public function publish(Request $request, string $type, int $id, PublishEntity $publishEntity): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('publish', $entity);

        $driver = Publishing::for($entity);
        $noun = ucfirst($driver->noun());
        $wasLive = $entity->getAttribute('publish_status') === PublishStatus::Published;

        try {
            $publishEntity->handle($request->user(), $entity);
        } catch (ValidationException $exception) {
            Toast::error($wasLive ? 'Changes not published' : "{$noun} not published")->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success($wasLive ? 'Changes published' : "{$noun} published")
            ->action('View on site', url($driver->publicPath($entity)))
            ->flash();

        return back();
    }

    public function unpublish(Request $request, string $type, int $id, UnpublishEntity $unpublishEntity): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('publish', $entity);

        $driver = Publishing::for($entity);
        $unpublishEntity->handle($request->user(), $entity);

        Toast::success(ucfirst($driver->noun()).' unpublished')
            ->description($driver->label($entity).' is a draft again and no longer on the website.')
            ->undo(route('admin.publishing.publish', [$type, $id]))
            ->flash();

        return back();
    }

    /** Workflow "Discard revision": the pending changes go, the live version stays. */
    public function discard(Request $request, string $type, int $id, DiscardPendingChanges $discardPendingChanges): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('update', $entity);

        $discardPendingChanges->handle($request->user(), $entity);

        Toast::success('Changes discarded')->description('The live version is unchanged.')->flash();

        return back();
    }

    public function schedule(SchedulePublishRequest $request, string $type, int $id, ScheduleEntity $scheduleEntity): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('publish', $entity);

        $at = $request->at();
        $scheduleEntity->handle($request->user(), $entity, $at);

        Toast::success('Scheduled')
            ->description('Goes live '.$at->timezone(SchedulePublishRequest::ZONE)->format('d M Y, H:i').' (Dhaka time).')
            ->action('Unschedule', route('admin.publishing.unschedule', [$type, $id]), 'post')
            ->flash();

        return back();
    }

    public function unschedule(Request $request, string $type, int $id, UnscheduleEntity $unscheduleEntity): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('publish', $entity);

        $unscheduleEntity->handle($request->user(), $entity);

        Toast::success('Schedule removed')->flash();

        return back();
    }

    /**
     * CMP-ADM-REV: the pending revision first, then what was published, newest first.
     */
    public function revisions(string $type, int $id): JsonResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('update', $entity);

        $pendingId = $entity->getAttribute('pending_revision_id');

        $rows = $entity->revisionHistory()->with('author:id,name')->orderByDesc('id')->get()
            ->sortBy(fn (Revision $revision): int => $revision->id === $pendingId ? 0 : 1)
            ->values()
            ->map(fn (Revision $revision): array => [
                'id' => $revision->id,
                'kind' => $revision->kind,
                'pending' => $revision->id === $pendingId,
                'note' => $revision->note,
                'author' => $revision->author?->name,
                'created_at' => $revision->created_at?->toIso8601String(),
                'publish_at' => $revision->publish_at?->toIso8601String(),
            ])->all();

        return response()->json(['revisions' => $rows]);
    }

    /**
     * What a revision changes compared with the version that is live (CMS-PUB-004).
     */
    public function diff(string $type, int $id, Revision $revision): JsonResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('update', $entity);
        abort_unless($revision->revisionable_type === $type && $revision->revisionable_id === $id, 404);

        $driver = Publishing::for($entity);

        return response()->json([
            'changes' => (new RevisionDiff($driver))->between($driver->snapshot($entity), $revision->payload),
        ]);
    }

    public function restore(Request $request, string $type, int $id, Revision $revision, RestoreRevision $restoreRevision): RedirectResponse
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('update', $entity);

        $restoreRevision->handle($request->user(), $entity, $revision);

        Toast::success('Revision restored')->description('It is now a pending change. Publish it when you are ready.')->flash();

        return back();
    }

    /** CMS-PUB-005: opens the signed preview of the pending revision (or the one in `?revision=`). */
    public function preview(Request $request, string $type, int $id): RedirectResponse
    {
        return redirect()->away($this->previewUrl($request, $type, $id));
    }

    /** The same link as JSON, for "Copy preview link" (shareable with reviewers, valid 60 minutes). */
    public function previewLink(Request $request, string $type, int $id): JsonResponse
    {
        return response()->json(['url' => $this->previewUrl($request, $type, $id), 'minutes' => PreviewLink::MINUTES]);
    }

    private function previewUrl(Request $request, string $type, int $id): string
    {
        $entity = $this->entity($type, $id);
        Gate::authorize('update', $entity);

        return PreviewLink::make($entity, $request->filled('revision') ? $request->integer('revision') : null);
    }

    private function entity(string $type, int $id): Model&PublishableEntity
    {
        return Publishing::find($type, $id);
    }
}
