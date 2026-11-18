<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use Illuminate\Validation\Rule;

/** Block 11, CMP-BLK-NEWS: optional category, 3 or 6 newest posts. */
final class LatestNewsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'category' => ['nullable', 'string', Rule::in(BlockData::newsCategories())],
            'limit' => ['required', 'integer', 'in:3,6'],
        ];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $category = ($data['category'] ?? '') === '' ? null : $data['category'];

        return [
            'heading' => $data['heading'] ?? null,
            'posts' => BlockData::news($category, (int) ($data['limit'] ?? 3)),
            'view_all' => ['label' => 'All news', 'url' => $category === null ? '/news' : '/news/category/'.$category, 'new_tab' => false],
        ];
    }
}
