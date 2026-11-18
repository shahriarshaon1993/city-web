<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use Illuminate\Validation\Rule;

/**
 * Block 15, CMP-BLK-FEATURES: 2–12 items, each with a Lucide icon from the allowlist or an image,
 * a title*, text and a link; 2, 3 or 4 columns; optional tilt.
 */
final class FeatureCardsSchema extends BlockSchema
{
    /** Mirrors `featureIcons` in resources/js/lib/feature-icons.ts. */
    public const array ICONS = [
        'award', 'badge-check', 'banknote', 'building-2', 'compass', 'file-check', 'hammer', 'handshake',
        'hard-hat', 'key-round', 'landmark', 'layers', 'leaf', 'map-pin', 'phone', 'ruler', 'scale',
        'shield-check', 'sun', 'trees', 'users', 'wrench',
    ];

    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'columns' => ['required', 'integer', 'in:2,3,4'],
            'tilt' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:2', 'max:12'],
            'items.*.icon' => ['nullable', 'string', Rule::in(self::ICONS)],
            'items.*.image' => BlockRules::image(),
            'items.*.title' => BlockRules::text(60, true),
            'items.*.text' => BlockRules::text(240),
            ...BlockRules::link('items.*.link'),
        ];
    }

    public function media(): array
    {
        return ['items.*.image'];
    }

    public function attributes(): array
    {
        return [
            'items' => 'items', 'items.*.icon' => 'icon', 'items.*.image' => 'item image', 'items.*.title' => 'item title', 'items.*.text' => 'item text',
            ...BlockRules::linkAttributes('items.*.link', 'item link'),
        ];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'columns' => in_array((int) ($data['columns'] ?? 3), [2, 3, 4], true) ? (int) $data['columns'] : 3,
            'tilt' => (bool) ($data['tilt'] ?? false),
            'items' => array_map(fn (array $item): array => [
                'icon' => ($item['icon'] ?? '') === '' ? null : $item['icon'],
                'image' => $context->image($item['image'] ?? null),
                'title' => (string) ($item['title'] ?? ''),
                'text' => ($item['text'] ?? '') === '' ? null : $item['text'],
                'link' => $context->link($item['link'] ?? null),
            ], $data['items'] ?? []),
        ];
    }
}
