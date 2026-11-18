<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 17, CMP-BLK-TIMELINE: heading*, 2–30 items (year 1950–2100*, title*, text, image), oldest first. */
final class TimelineSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'items' => ['required', 'array', 'min:2', 'max:30'],
            'items.*.year' => ['required', 'integer', 'between:1950,2100'],
            'items.*.title' => BlockRules::text(80, true),
            'items.*.text' => BlockRules::text(300),
            'items.*.image' => BlockRules::image(),
        ];
    }

    public function media(): array
    {
        return ['items.*.image'];
    }

    public function attributes(): array
    {
        return ['items' => 'milestones', 'items.*.year' => 'year', 'items.*.title' => 'milestone title', 'items.*.text' => 'milestone text', 'items.*.image' => 'milestone image'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $items = array_map(fn (array $item): array => [
            'year' => (int) ($item['year'] ?? 0),
            'title' => (string) ($item['title'] ?? ''),
            'text' => ($item['text'] ?? '') === '' ? null : $item['text'],
            'image' => $context->image($item['image'] ?? null),
        ], $data['items'] ?? []);

        usort($items, fn (array $a, array $b): int => $a['year'] <=> $b['year']);

        return ['heading' => (string) ($data['heading'] ?? ''), 'items' => $items];
    }
}
