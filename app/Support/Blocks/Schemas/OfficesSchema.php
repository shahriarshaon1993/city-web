<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 25, CMP-BLK-OFFICES: heading, office ids (empty = every office). */
final class OfficesSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'office_ids' => ['nullable', 'array', 'max:20'],
            'office_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return ['office_ids' => 'offices'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $ids = array_map(intval(...), $data['office_ids'] ?? []);

        return [
            'heading' => $data['heading'] ?? null,
            'offices' => array_values(array_filter(
                BlockData::offices(),
                fn (array $office): bool => $ids === [] || in_array($office['id'], $ids, true),
            )),
        ];
    }
}
