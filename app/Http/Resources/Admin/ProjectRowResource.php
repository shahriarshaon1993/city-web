<?php

namespace App\Http\Resources\Admin;

use App\Enums\PublishStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the projects list (ADM-SCR-01). `can` says what the signed-in user may do (ARC-005);
 * the Policies re-check on the server.
 *
 * @mixin Project
 */
class ProjectRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $live = $this->publish_status === PublishStatus::Published;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumb' => $this->cover?->thumbUrl(),
            'segment' => ['value' => $this->segment->value, 'label' => $this->segment->label()],
            'lifecycle' => ['value' => $this->lifecycle->value, 'label' => $this->lifecycle->label()],
            'booking' => $this->booking_state->label(),
            'area' => $this->location?->name,
            'city' => $this->location?->parent?->name,
            'status' => ['value' => $this->publish_status->value, 'label' => $this->publish_status->label()],
            // CMS-PUB: changes waiting on a published project, and when something goes live by itself.
            'pending' => $live && $this->pending_revision_id !== null,
            'scheduled_at' => match (true) {
                $this->publish_status === PublishStatus::Scheduled => $this->published_at?->toIso8601String(),
                $live && $this->pending_revision_id !== null => $this->pendingRevision?->publish_at?->toIso8601String(),
                default => null,
            },
            'is_featured' => $this->is_featured,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'edit_url' => route('admin.projects.edit', $this->resource),
            // HELP-04: published projects link to the site; a draft or pending one opens its preview.
            'public_url' => $live ? url("/projects/{$this->slug}") : null,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'publish' => $actor->can('publish', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
                'create' => $actor->can('create', Project::class),
            ],
        ];
    }
}
