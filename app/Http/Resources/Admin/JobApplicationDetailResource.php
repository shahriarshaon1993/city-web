<?php

namespace App\Http\Resources\Admin;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Support\Careers\ReviewReasons;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The application drawer (JSON, ADM-SCR-10, FR-CAR-005): everything the applicant sent, the pipeline
 * status, the internal notes and the authorised CV link. The CV URL is the download route, never a file path.
 *
 * @mixin JobApplication
 */
class JobApplicationDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $digits = preg_replace('/\D+/', '', $this->phone) ?? '';

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'links' => [
                'call' => "tel:{$this->phone}",
                'whatsapp' => $digits === '' ? null : "https://wa.me/{$digits}",
                'email' => "mailto:{$this->email}",
            ],
            'job' => $this->job === null ? null : [
                'id' => $this->job->id,
                'title' => $this->job->title,
                'edit_url' => $actor->can('jobs.view') ? route('admin.careers.edit', $this->job) : null,
            ],
            'current_position' => $this->current_position,
            'years_experience' => $this->years_experience,
            'expected_salary' => $this->expected_salary,
            'linkedin_url' => $this->linkedin_url,
            'cover_letter' => $this->cover_letter,
            'cv' => [
                'name' => $this->cv_name,
                'size' => $this->cv_size,
                'url' => route('admin.applications.cv', $this->resource),
            ],
            'status' => $this->status->value,
            'statuses' => array_map(fn (ApplicationStatus $case): array => ['value' => $case->value, 'label' => $case->label()], ApplicationStatus::cases()),
            'notes' => $this->notes,
            'needs_review' => $this->review_reason !== null,
            'review_label' => ReviewReasons::label($this->review_reason),
            'consent_text' => $this->consent_text,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'removal_at' => $this->updated_at?->addDays(JobApplication::RETENTION_DAYS)->toIso8601String(),
            'can' => [
                'update' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
