<?php

namespace App\Http\Resources\Site;

use App\Models\JobPost;
use App\Queries\Site\CareersQuery;
use App\Support\Careers\JobDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One PG-15 role row (`JobRow` in resources/js/types/careers.ts). The countdown text is worked out here,
 * so SSR and the browser agree.
 *
 * @mixin JobPost
 */
class JobRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => $this->path(),
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->office->name ?? CareersQuery::SITES_LABEL,
            'employment_type' => $this->employment_type->value,
            'employment_type_label' => $this->employment_type->label(),
            'deadline' => $this->deadline->toDateString(),
            'closes_label' => $this->closesLabel(),
        ];
    }

    /** LAY-CAREER-DETAIL "deadline countdown text"; null once the job is closed. */
    protected function closesLabel(): ?string
    {
        if ($this->isClosed()) {
            return null;
        }

        $days = (int) JobDates::today()->diffInDays($this->deadline);

        return match (true) {
            $days === 0 => 'Closes today',
            $days === 1 => 'Closes tomorrow',
            default => "Closes in {$days} days",
        };
    }
}
