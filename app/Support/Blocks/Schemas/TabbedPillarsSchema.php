<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 18, CMP-BLK-PILLARS: heading, 2–5 tabs (label*, title*, text*, image). */
final class TabbedPillarsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'tabs' => ['required', 'array', 'min:2', 'max:5'],
            'tabs.*.label' => BlockRules::text(24, true),
            'tabs.*.title' => BlockRules::text(80, true),
            'tabs.*.text' => BlockRules::text(500, true),
            'tabs.*.image' => BlockRules::image(),
        ];
    }

    public function media(): array
    {
        return ['tabs.*.image'];
    }

    public function attributes(): array
    {
        return ['tabs' => 'tabs', 'tabs.*.label' => 'tab label', 'tabs.*.title' => 'tab title', 'tabs.*.text' => 'tab text', 'tabs.*.image' => 'tab image'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'tabs' => array_map(fn (array $tab): array => [
                'label' => (string) ($tab['label'] ?? ''),
                'title' => (string) ($tab['title'] ?? ''),
                'text' => (string) ($tab['text'] ?? ''),
                'image' => $context->image($tab['image'] ?? null),
            ], $data['tabs'] ?? []),
        ];
    }
}
