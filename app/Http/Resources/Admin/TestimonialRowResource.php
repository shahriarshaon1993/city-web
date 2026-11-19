<?php

namespace App\Http\Resources\Admin;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the testimonials list.
 *
 * @mixin Testimonial
 */
class TestimonialRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'quote' => $this->quote,
            'person_name' => $this->person_name,
            'designation' => $this->designation,
            'project' => $this->project === null ? null : ['id' => $this->project->id, 'name' => $this->project->name],
            'thumb' => $this->photo?->thumbUrl(),
            'is_nrb' => $this->is_nrb,
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
