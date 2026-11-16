<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\Redirect;

/**
 * DEL-05: a project that comes back from the Recycle bin stops redirecting. The rule written by
 * `DeleteProject` is removed for good (not into the bin) and `archived_redirect_to` is cleared.
 */
final class ClearProjectRedirect
{
    public function handle(Project $project): void
    {
        if ($project->archived_redirect_to === null) {
            return;
        }

        Redirect::query()
            ->where('source', "/projects/{$project->slug}")
            ->where('target', $project->archived_redirect_to)
            ->get()
            ->each->forceDelete();

        $project->archived_redirect_to = null;
    }
}
