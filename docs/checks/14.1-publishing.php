<?php

/**
 * Phase 14.1 check: pending revisions, the lock, preview data, revision diff, schedule + scheduler,
 * restore, discard, for a project and an area. Everything is rolled back.
 * Run: php artisan tinker docs/checks/14.1-publishing.php < /dev/null   (needs `db:seed` first)
 */

use App\Actions\Publishing\DiscardPendingChanges;
use App\Actions\Publishing\PublishDueEntities;
use App\Actions\Publishing\PublishEntity;
use App\Actions\Publishing\RestoreRevision;
use App\Actions\Publishing\SaveEntityChanges;
use App\Actions\Publishing\ScheduleEntity;
use App\Actions\Publishing\UnpublishEntity;
use App\Actions\Publishing\UnscheduleEntity;
use App\Enums\PublishStatus;
use App\Models\Location;
use App\Models\Project;
use App\Models\Revision;
use App\Models\User;
use App\Queries\Admin\PublishingState;
use App\Queries\Site\AreaPage;
use App\Queries\Site\ProjectDetail;
use App\Support\Publishing\PreviewLink;
use App\Support\Publishing\Publishing;
use App\Support\Publishing\RevisionDiff;
use App\Support\Publishing\StaleEntityException;
use Illuminate\Support\Facades\DB;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

DB::beginTransaction();

try {
    $actor = User::role('Super Admin')->firstOrFail();
    $save = app(SaveEntityChanges::class);
    $publish = app(PublishEntity::class);

    // --- project: a published project saves to a pending revision --------------------------------
    $project = Project::query()->published()->orderBy('id')->firstOrFail();
    $liveTagline = $project->tagline;
    $driver = Publishing::for($project);
    $form = $driver->snapshot($project);
    $form['tagline'] = 'Pending tagline for the check';
    $lock = $project->lock_version;

    $wasPending = $save->handle($actor, $project, $form, $lock);
    $project->refresh();
    $say('published save goes to the pending revision', $wasPending, true);
    $say('live tagline untouched', $project->tagline, $liveTagline);
    $say('pending revision stored', $project->pending_revision_id !== null, true);
    $say('lock went up', $project->lock_version, $lock + 1);
    $say('editor form starts from pending', PublishingState::form($project)['tagline'], 'Pending tagline for the check');

    // CMS-PUB-006: a second tab with the old lock.
    try {
        $save->handle($actor, $project, $form, $lock);
        $say('stale save refused', 'saved', 'StaleEntityException');
    } catch (StaleEntityException $exception) {
        $say('stale save refused', $exception->details()['lock_version'], $project->lock_version);
    }
    $save->handle($actor, $project, $form, $lock, overwrite: true);
    $say('overwrite is accepted', true, true);
    $project->refresh();

    // CMS-PUB-005: preview data from the pending revision, public data unchanged.
    $copy = $driver->hydrate($project, $project->pendingRevision->payload, true);
    $say('preview shows the pending tagline', ProjectDetail::preview($copy)['project']['tagline'], 'Pending tagline for the check');
    $say('public page still shows the live tagline', ProjectDetail::find($project->slug)['project']['tagline'] ?? null, $liveTagline);
    $say('signed preview link', str_contains(PreviewLink::make($project), 'signature='), true);

    // CMS-PUB-004: the diff.
    $changes = (new RevisionDiff($driver))->between($driver->snapshot($project), $project->pendingRevision->payload);
    $say('diff lists exactly the tagline', array_column($changes, 'key'), ['tagline']);
    $say('diff after', $changes[0]['after'] ?? null, 'Pending tagline for the check');

    // Publish changes → live, revision stored, pending cleared.
    $publish->handle($actor, $project);
    $project->refresh();
    $say('publish changes: live tagline', $project->tagline, 'Pending tagline for the check');
    $say('publish changes: pending cleared', $project->pending_revision_id, null);
    $say('publish stored a revision', $project->revisions()->where('kind', Revision::PUBLISHED)->count() >= 1, true);

    // Schedule the pending changes, then the scheduler (CMS-PUB-003).
    $form['tagline'] = 'Scheduled tagline';
    $save->handle($actor, $project, $form, $project->lock_version);
    $project->refresh();
    app(ScheduleEntity::class)->handle($actor, $project, now()->addMinutes(2));
    $say('pending schedule stored', $project->pendingRevision->fresh()->publish_at !== null, true);
    $say('scheduler before the time: nothing', app(PublishDueEntities::class)->handle()['published'], 0);
    $project->pendingRevision->forceFill(['publish_at' => now()->subMinute()])->save();
    $say('scheduler publishes the due revision', app(PublishDueEntities::class)->handle()['published'], 1);
    $say('scheduled changes are live', $project->fresh()->tagline, 'Scheduled tagline');

    // Draft → Scheduled → Published by the scheduler (AC-PROJ-04).
    app(UnpublishEntity::class)->handle($actor, $project);
    $project->refresh();
    $say('unpublish → draft', $project->publish_status, PublishStatus::Draft);
    app(ScheduleEntity::class)->handle($actor, $project, now()->addMinutes(5));
    $project->refresh();
    $say('schedule → scheduled', $project->publish_status, PublishStatus::Scheduled);
    $say('a scheduled project is not public', Project::query()->published()->whereKey($project->id)->exists(), false);
    app(UnscheduleEntity::class)->handle($actor, $project);
    $say('unschedule → draft', $project->fresh()->publish_status, PublishStatus::Draft);
    app(ScheduleEntity::class)->handle($actor, $project->fresh(), now()->addMinutes(5));
    $project->refresh()->forceFill(['published_at' => now()->subMinute()])->saveQuietly();
    $say('scheduler publishes the due project', app(PublishDueEntities::class)->handle()['published'], 1);
    $say('project is public again', Project::query()->published()->whereKey($project->id)->exists(), true);

    // Restore a revision → pending; discard it.
    $project->refresh();
    $history = $project->revisions()->where('kind', Revision::PUBLISHED)->orderBy('id')->first();
    app(RestoreRevision::class)->handle($actor, $project, $history);
    $project->refresh();
    $say('restore creates a pending revision', $project->pending_revision_id !== null, true);
    app(DiscardPendingChanges::class)->handle($actor, $project);
    $say('discard clears it', $project->fresh()->pending_revision_id, null);

    // A draft saves to the live row (not public anyway) and drops a stale pending copy.
    app(UnpublishEntity::class)->handle($actor, $project->fresh());
    $draft = $project->fresh();
    $form['tagline'] = 'Draft tagline';
    $say('draft save writes the live row', $save->handle($actor, $draft, $form, $draft->lock_version), false);
    $say('draft tagline saved', $draft->fresh()->tagline, 'Draft tagline');
    $publish->handle($actor, $draft->fresh());

    // 30 revisions are kept.
    for ($i = 0; $i < 33; $i++) {
        $form['tagline'] = "Round {$i}";
        $save->handle($actor, $project->fresh(), $form, null);
        $publish->handle($actor, $project->fresh());
    }
    $say('only the last 30 published revisions are kept', $project->revisions()->where('kind', Revision::PUBLISHED)->count(), 30);

    // --- area ---------------------------------------------------------------------------------------
    $area = Location::query()->areas()->published()->orderBy('id')->firstOrFail();
    $areaDriver = Publishing::for($area);
    $areaForm = $areaDriver->snapshot($area);
    $areaForm['intro'] = '<p>Pending intro for the check.</p>';
    $liveIntro = $area->intro;
    $say('area: published save is pending', $save->handle($actor, $area, $areaForm, $area->lock_version), true);
    $area->refresh();
    $say('area: live intro untouched', $area->intro, $liveIntro);
    $say('area: preview shows pending intro', AreaPage::preview($areaDriver->hydrate($area, $area->pendingRevision->payload))['intro'], '<p>Pending intro for the check.</p>');
    $areaChanges = (new RevisionDiff($areaDriver))->between($areaDriver->snapshot($area), $area->pendingRevision->payload);
    $say('area: diff is the intro as rich text', [$areaChanges[0]['key'] ?? null, $areaChanges[0]['kind'] ?? null], ['intro', 'html']);
    $publish->handle($actor, $area);
    $say('area: publish changes', $area->fresh()->intro, '<p>Pending intro for the check.</p>');
} finally {
    DB::rollBack();
}
