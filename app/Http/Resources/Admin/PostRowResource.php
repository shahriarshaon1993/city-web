<?php

namespace App\Http\Resources\Admin;

use App\Enums\PublishStatus;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the posts list (ADM-SCR-08). `can` says what the signed-in user may do (ARC-005); the Policy
 * re-checks on the server.
 *
 * @mixin Post
 */
class PostRowResource extends JsonResource
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
            'category' => $this->category?->name,
            'author' => $this->author?->name,
            'is_featured' => $this->is_featured,
            'status' => ['value' => $this->publish_status->value, 'label' => $this->publish_status->label()],
            'pending' => $live && $this->pending_revision_id !== null,
            'scheduled_at' => match (true) {
                $this->publish_status === PublishStatus::Scheduled => $this->published_at?->toIso8601String(),
                $live && $this->pending_revision_id !== null => $this->pendingRevision?->publish_at?->toIso8601String(),
                default => null,
            },
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'edit_url' => route('admin.news.edit', $this->resource),
            'public_url' => $live ? url($this->path()) : null,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'publish' => $actor->can('publish', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
                'duplicate' => $actor->can('create', Post::class),
            ],
        ];
    }
}
