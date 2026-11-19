<?php

namespace App\Http\Resources\Admin;

use App\Enums\JobStatus;
use App\Models\JobPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the jobs list (ADM-SCR-10). `deadline_passed` marks an Open role the 00:05 scheduler has not
 * closed yet. `can` says what the signed-in user may do (ARC-005); the Policy re-checks on the server.
 *
 * @mixin JobPost
 */
class JobPostRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $open = $this->status === JobStatus::Open;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'path' => $this->path(),
            'department' => $this->department,
            'office' => $this->office?->name,
            'employment_type' => ['value' => $this->employment_type->value, 'label' => $this->employment_type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'deadline' => $this->deadline->toDateString(),
            'deadline_passed' => $open && $this->isClosed(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'applications_count' => (int) $this->getAttribute('applications_count'),
            'new_applications_count' => (int) $this->getAttribute('new_applications_count'),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'edit_url' => route('admin.careers.edit', $this->resource),
            'public_url' => $this->isAcceptingApplications() || $this->status === JobStatus::Closed ? url($this->path()) : null,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
                'duplicate' => $actor->can('create', JobPost::class),
                'applications' => $actor->can('applications.manage'),
            ],
        ];
    }
}
