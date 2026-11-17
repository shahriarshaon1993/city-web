<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Models\Project;
use App\Models\User;

/**
 * FR-PROJ-010: the featured star. A newly featured project goes to the end of the manual order. The
 * star is a quick change on the live row (CRUD-01), so a pending revision is kept in step instead of
 * undoing it when it is published.
 */
final class SetProjectFeatured
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Project $project, bool $featured): Project
    {
        if ($project->is_featured === $featured) {
            return $project;
        }

        $project->forceFill([
            'is_featured' => $featured,
            'featured_order' => $featured ? ((int) Project::query()->where('is_featured', true)->max('featured_order')) + 1 : null,
        ])->save();

        $pending = $project->pendingRevision;
        $pending?->forceFill(['payload' => [...$pending->payload, 'is_featured' => $featured]])->save();

        $this->logActivity->handle(
            'projects',
            'updated',
            ($featured ? 'Project featured: ' : 'Project no longer featured: ').$project->name,
            $actor,
            $project,
            ['label' => $project->name],
        );

        return $project;
    }
}
