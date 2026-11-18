<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 14, CMP-BLK-IMGTXT: heading*, body*, image*, image side, CTA, image reveal. */
final class ImageTextSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80, true),
            'body' => BlockRules::html(true),
            'image' => BlockRules::image(true),
            'image_side' => BlockRules::choice(['left', 'right']),
            ...BlockRules::link('cta'),
            'reveal' => BlockRules::choice(['mask', 'none']),
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
        return ['image_side' => 'image side', ...BlockRules::linkAttributes('cta', 'button')];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => (string) ($data['heading'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'image' => $context->image($data['image'] ?? null),
            'image_side' => $data['image_side'] ?? 'right',
            'cta' => $context->link($data['cta'] ?? null),
            'reveal' => $data['reveal'] ?? 'mask',
        ];
    }
}
