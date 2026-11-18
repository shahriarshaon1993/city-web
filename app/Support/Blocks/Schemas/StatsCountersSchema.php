<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 7, CMP-BLK-STATS: 2–6 items; value ≤ 10^9, suffix ≤ 6 (the spec says 4 but its own example "M sft" has 5), label ≤ 40, 0–1 decimals. */
final class StatsCountersSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'items' => ['required', 'array', 'min:2', 'max:6'],
            'items.*.value' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'items.*.suffix' => BlockRules::text(6),
            'items.*.label' => BlockRules::text(40, true),
            'items.*.decimals' => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function attributes(): array
    {
        return ['items' => 'figures', 'items.*.value' => 'number', 'items.*.suffix' => 'suffix', 'items.*.label' => 'label', 'items.*.decimals' => 'decimals'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'items' => array_map(fn (array $item): array => [
                'value' => ($item['value'] ?? 0) + 0,
                'suffix' => ($item['suffix'] ?? '') === '' ? null : $item['suffix'],
                'label' => (string) ($item['label'] ?? ''),
                'decimals' => (int) ($item['decimals'] ?? 0) === 1 ? 1 : 0,
            ], $data['items'] ?? []),
        ];
    }
}
