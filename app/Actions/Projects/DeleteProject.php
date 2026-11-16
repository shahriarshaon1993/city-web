<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Redirects\SaveRedirect;
use App\Enums\PublishStatus;
use App\Models\Project;
use App\Models\RecycleBinEntry;
use App\Models\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DEL-02 / AC-PROJ-06: Delete project = Recycle bin. A published project may name a page its address
 * should redirect to: that is stored as `archived_redirect_to` and as a 301 rule of the redirect
 * manager, so the old URL answers at once. Without one the address answers 410 (public side, 13.3).
 */
final class DeleteProject
{
    public function __construct(
        private readonly MoveToRecycleBin $moveToRecycleBin,
        private readonly SaveRedirect $saveRedirect,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  string|null  $redirectTo  an address on this site (`/projects/other-project`)
     *
     * @throws ValidationException when the redirect would loop or cannot be stored
     */
    public function handle(User $actor, Project $project, ?string $redirectTo = null): RecycleBinEntry
    {
        return DB::transaction(function () use ($actor, $project, $redirectTo): RecycleBinEntry {
            $wasPublished = $project->publish_status === PublishStatus::Published;
            $redirectTo = $wasPublished && $redirectTo !== null && $redirectTo !== '' ? $redirectTo : null;

            if ($redirectTo !== null) {
                $source = "/projects/{$project->slug}";

                try {
                    $this->saveRedirect->handle(
                        $actor,
                        ['source' => $source, 'code' => 301, 'target' => $redirectTo, 'is_active' => true],
                        Redirect::query()->where('source', $source)->first(),
                    );
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['redirect_to' => $exception->validator->errors()->first()]);
                }

                $project->forceFill(['archived_redirect_to' => $redirectTo])->saveQuietly();
            }

            $entry = $this->moveToRecycleBin->handle($actor, $project);

            if ($redirectTo !== null) {
                $this->logActivity->handle('projects', 'updated', "Project redirect set: {$project->name}", $actor, $project, ['redirect_to' => $redirectTo]);
            }

            return $entry;
        });
    }
}
