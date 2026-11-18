<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 28, CMP-BLK-PARALLAX: image* (≥ 1920 px), heading, text, height (`md` 60vh, `lg` 80vh), parallax. */
final class ParallaxBandSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'image' => BlockRules::image(true, 1920),
            'heading' => BlockRules::text(80),
            'text' => BlockRules::text(200),
            'height' => BlockRules::choice(['md', 'lg']),
            'parallax' => ['nullable', 'boolean'],
        ];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'image' => $context->image($data['image'] ?? null),
            'heading' => ($data['heading'] ?? '') === '' ? null : $data['heading'],
            'text' => ($data['text'] ?? '') === '' ? null : $data['text'],
            'height' => $data['height'] ?? 'md',
            'parallax' => (bool) ($data['parallax'] ?? true),
        ];
    }
}
