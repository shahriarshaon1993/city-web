<?php

namespace App\Http\Resources\Admin;

use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the pages list (ADM-SCR-06). `can` says what the signed-in user may do (ARC-005); the
 * Policy re-checks on the server. A system page cannot be deleted and says why (HELP-08).
 *
 * @mixin Page
 */
class PageRowResource extends JsonResource
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
            'title' => $this->title,
            'path' => $this->path(),
            'template' => $this->template,
            'is_system' => $this->is_system,
            'blocks_count' => (int) $this->blocks_count,
            'status' => ['value' => $this->publish_status->value, 'label' => $this->publish_status->label()],
            'pending' => $live && $this->pending_revision_id !== null,
            'scheduled_at' => match (true) {
                $this->publish_status === PublishStatus::Scheduled => $this->published_at?->toIso8601String(),
                $live && $this->pending_revision_id !== null => $this->pendingRevision?->publish_at?->toIso8601String(),
                default => null,
            },
            'updated_at' => $this->updated_at?->toIso8601String(),
            'edit_url' => route('admin.pages.edit', $this->resource),
            'public_url' => $live ? url($this->path()) : null,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'publish' => $actor->can('publish', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
                'duplicate' => $actor->can('create', Page::class) && ! $this->is_system && ! $this->isLegal(),
            ],
            'delete_reason' => $this->is_system ? 'System pages are part of the website and cannot be deleted.' : null,
        ];
    }
}
