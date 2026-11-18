<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 19, CMP-BLK-TEAM: heading, group* (`leadership`, `management`, `team`, FR-SOC-004). */
final class TeamGridSchema extends BlockSchema
{
    public function rules(): array
    {
        return ['heading' => BlockRules::text(80), 'group' => BlockRules::choice(['leadership', 'management', 'team'])];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $group = in_array($data['group'] ?? null, ['leadership', 'management', 'team'], true) ? $data['group'] : 'team';

        return ['heading' => $data['heading'] ?? null, 'group' => $group, 'members' => BlockData::team($group)];
    }
}
