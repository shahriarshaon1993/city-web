<?php

namespace App\Http\Resources\Admin;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the amenities list. `usage` carries what DEL-03 needs: how many projects list the
 * amenity and the first few, so the delete dialog can name them with links.
 *
 * @mixin Amenity
 */
class AmenityRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $count = (int) $this->getAttribute('projects_count');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'icon' => $this->icon,
            'projects_count' => $count,
            'projects' => $count === 0 ? [] : $this->projects()->orderBy('name')->limit(8)->get(['projects.id', 'projects.name'])
                ->map(fn ($project): array => ['label' => $project->name, 'href' => route('admin.projects.edit', $project->id)])->all(),
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
