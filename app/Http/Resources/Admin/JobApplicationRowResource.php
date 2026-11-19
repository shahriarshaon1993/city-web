<?php

namespace App\Http\Resources\Admin;

use App\Models\JobApplication;
use App\Support\Careers\ReviewReasons;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the applications list (ADM-SCR-10). A row with `review_reason` was stored but flagged by the
 * spam rules (FR-LEAD-003) and shows "Needs review".
 *
 * @mixin JobApplication
 */
class JobApplicationRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'job' => $this->job === null ? null : ['id' => $this->job->id, 'title' => $this->job->title],
            'current_position' => $this->current_position,
            'years_experience' => $this->years_experience,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'needs_review' => $this->review_reason !== null,
            'review_label' => ReviewReasons::label($this->review_reason),
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => [
                'update' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
