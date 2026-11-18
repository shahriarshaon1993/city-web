<?php

namespace App\Support\Blocks\Schemas;

use App\Models\Project;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 9, CMP-BLK-TESTIMONIALS: source `all`, `nrb`, `project` or `manual`, limit 3–12, carousel or grid. */
final class TestimonialsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'source' => BlockRules::choice(['all', 'nrb', 'project', 'manual']),
            'ids' => ['required_if:source,project,manual', 'nullable', 'array', 'max:12'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
            'layout' => BlockRules::choice(['carousel', 'grid']),
            'limit' => ['required', 'integer', 'between:3,12'],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'testimonials or projects'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $all = BlockData::testimonials();
        $ids = array_values(array_map(intval(...), $data['ids'] ?? []));

        $items = match ($data['source'] ?? 'all') {
            'nrb' => array_values(array_filter($all, fn (array $item): bool => $item['is_nrb'])),
            'manual' => array_values(array_filter(array_map(fn (int $id): ?array => array_find($all, fn (array $item): bool => $item['id'] === $id), $ids))),
            'project' => $this->forProjects($all, $ids),
            default => $all,
        };

        return [
            'heading' => $data['heading'] ?? null,
            'layout' => $data['layout'] ?? 'carousel',
            'items' => array_slice($items, 0, (int) ($data['limit'] ?? 6)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $all
     * @param  list<int>  $projectIds
     * @return list<array<string, mixed>>
     */
    private function forProjects(array $all, array $projectIds): array
    {
        $urls = Project::query()->whereIn('id', $projectIds)->pluck('slug')->map(fn (string $slug): string => '/projects/'.$slug)->all();

        return array_values(array_filter($all, fn (array $item): bool => in_array($item['project']['url'] ?? null, $urls, true)));
    }
}
