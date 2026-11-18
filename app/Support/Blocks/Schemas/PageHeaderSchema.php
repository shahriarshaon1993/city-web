<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 2, CMP-BLK-PAGEHEAD: title ≤ 80, intro, background image, breadcrumb toggle. */
final class PageHeaderSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'title' => BlockRules::text(80, true),
            'intro' => BlockRules::text(400),
            'image' => BlockRules::image(),
            'breadcrumb' => ['nullable', 'boolean'],
        ];
    }

    public function media(): array
    {
        return ['image'];
    }

    public function attributes(): array
    {
        return ['image' => 'background image'];
    }

    public function headline(array $data): ?string
    {
        return $data['title'] ?? null;
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'title' => (string) ($data['title'] ?? ''),
            'intro' => $data['intro'] ?? null,
            'image' => $context->image($data['image'] ?? null),
            'breadcrumbs' => ($data['breadcrumb'] ?? true) ? $context->breadcrumbs() : null,
        ];
    }
}
