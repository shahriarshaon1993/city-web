<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 27, CMP-BLK-QUOTE: quote* ≤ 400, person*, role, photo. */
final class QuoteSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'quote' => BlockRules::text(400, true),
            'person' => BlockRules::text(80, true),
            'role' => BlockRules::text(80),
            'photo' => BlockRules::image(),
        ];
    }

    public function media(): array
    {
        return ['photo'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'quote' => (string) ($data['quote'] ?? ''),
            'person' => (string) ($data['person'] ?? ''),
            'role' => ($data['role'] ?? '') === '' ? null : $data['role'],
            'photo' => $context->image($data['photo'] ?? null),
        ];
    }
}
