<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Project;
use App\Models\User;
use App\Support\Projects\PublishChecklist;
use Illuminate\Validation\ValidationException;

/**
 * AC-PROJ-03: Publish now. Refused, with every unmet rule named, until the checklist passes; the
 * Draft → Scheduled path and pending revisions belong to 14.1. The `published_at` of a project that
 * was published before is kept.
 */
final class PublishProject
{
    public function __construct(
        private readonly PublishChecklist $checklist,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @throws ValidationException when the checklist has open items
     */
    public function handle(User $actor, Project $project): Project
    {
        if ($project->publish_status === PublishStatus::Published) {
            return $project;
        }

        $open = $this->checklist->failing($project);

        if ($open !== []) {
            $labels = implode(', ', array_map(fn (array $item): string => mb_strtolower($item['label']), array_slice($open, 0, 3)));
            $more = count($open) > 3 ? ' and '.(count($open) - 3).' more' : '';

            throw ValidationException::withMessages([
                'publish' => count($open).' checklist '.(count($open) === 1 ? 'item is' : 'items are').' still open: '.$labels.$more.'.',
            ]);
        }

        $project->forceFill([
            'publish_status' => PublishStatus::Published,
            'published_at' => $project->published_at ?? now(),
        ])->save();

        $this->logActivity->handle('projects', 'published', "Project published: {$project->name}", $actor, $project, ['label' => $project->name]);

        return $project;
    }
}
