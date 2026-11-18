<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 12, CMP-BLK-CTA: heading* ≤ 70, text, primary CTA*, secondary CTA, style, image (image style). */
final class CtaBandSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(70, true),
            'text' => BlockRules::text(200),
            ...BlockRules::link('primary_cta', true),
            ...BlockRules::link('secondary_cta'),
            'style' => BlockRules::choice(['brand', 'gradient', 'image']),
            'image' => [...BlockRules::image(), 'required_if:style,image'],
        ];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function attributes(): array
    {
        return [
            'image' => 'background image',
            ...BlockRules::linkAttributes('primary_cta', 'main button'),
            ...BlockRules::linkAttributes('secondary_cta', 'second button'),
        ];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $primary = $context->link($data['primary_cta'] ?? null);

        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'text' => $data['text'] ?? null,
            // The type needs a button: one whose target is gone points home instead of breaking the band.
            'primary_cta' => $primary ?? ['label' => (string) ($data['primary_cta']['label'] ?? 'Contact us'), 'url' => '/', 'new_tab' => false],
            'secondary_cta' => $context->link($data['secondary_cta'] ?? null),
            'style' => $data['style'] ?? 'gradient',
            'image' => ($data['style'] ?? '') === 'image' ? $context->image($data['image'] ?? null) : null,
        ];
    }
}
