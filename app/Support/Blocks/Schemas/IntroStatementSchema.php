<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 3, CMP-BLK-INTRO: eyebrow, heading*, body (rich, ≤ 120 words), CTA, side image. */
final class IntroStatementSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'eyebrow' => BlockRules::text(40),
            'heading' => BlockRules::text(120, true),
            'body' => BlockRules::html(maxWords: 120),
            ...BlockRules::link('cta'),
            'image' => BlockRules::image(),
        ];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function html(): array
    {
        return ['body'];
    }

    public function attributes(): array
    {
        return ['image' => 'side image', ...BlockRules::linkAttributes('cta', 'button')];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'eyebrow' => $data['eyebrow'] ?? null,
            'heading' => (string) ($data['heading'] ?? ''),
            'body' => $data['body'] ?? null,
            'cta' => $context->link($data['cta'] ?? null),
            'image' => $context->image($data['image'] ?? null),
        ];
    }
}
