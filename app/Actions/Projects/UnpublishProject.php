<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Project;
use App\Models\User;

/** CRUD-08: Unpublish = back to Draft, not public. */
final class UnpublishProject
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Project $project): Project
    {
        if ($project->publish_status === PublishStatus::Draft) {
            return $project;
        }

        $project->forceFill(['publish_status' => PublishStatus::Draft])->save();

        $this->logActivity->handle('projects', 'unpublished', "Project unpublished: {$project->name}", $actor, $project, ['label' => $project->name]);

        return $project;
    }
}
