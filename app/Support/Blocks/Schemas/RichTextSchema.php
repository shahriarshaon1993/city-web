<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 13, CMP-BLK-RICH: body* (rich), width `narrow` (68ch) or `wide`. */
final class RichTextSchema extends BlockSchema
{
    public function rules(): array
    {
        return ['body' => BlockRules::html(true), 'width' => BlockRules::choice(['narrow', 'wide'])];
    }

    public function html(): array
    {
        return ['body'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return ['body' => (string) ($data['body'] ?? ''), 'width' => $data['width'] ?? 'narrow'];
    }
}
