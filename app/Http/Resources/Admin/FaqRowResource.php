<?php

namespace App\Http\Resources\Admin;

use App\Enums\FaqPlacement;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the FAQ list. `projects_count` / `locations_count` come from the table's `withCount`.
 *
 * @mixin Faq
 */
class FaqRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer_text' => trim(html_entity_decode(strip_tags(str_replace(['</p>', '</li>'], ' ', $this->answer)))),
            'category' => ['value' => $this->category->value, 'label' => $this->category->label()],
            'show_on' => array_map(fn (string $value): string => FaqPlacement::tryFrom($value)?->label() ?? $value, $this->show_on),
            'attached' => (int) $this->getAttribute('projects_count') + (int) $this->getAttribute('locations_count') + (int) $this->getAttribute('facet_pages_count'),
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
