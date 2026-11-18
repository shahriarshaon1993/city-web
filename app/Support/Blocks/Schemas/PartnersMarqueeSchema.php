<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 10, CMP-BLK-MARQUEE: optional category (FR-SOC-002), speed, direction; at least 4 partners. */
final class PartnersMarqueeSchema extends BlockSchema
{
    public const array CATEGORIES = ['bank', 'supplier', 'consultant', 'group_company'];

    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'category' => BlockRules::choice(self::CATEGORIES, false),
            'speed' => BlockRules::choice(['slow', 'normal']),
            'direction' => BlockRules::choice(['left', 'right']),
        ];
    }

    public function checks(array $data): array
    {
        return count($this->partners($data)) < 4 ? ['category' => 'The strip needs at least 4 partners; choose another category or add partners.'] : [];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'speed' => $data['speed'] ?? 'normal',
            'direction' => $data['direction'] ?? 'left',
            'partners' => $this->partners($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function partners(array $data): array
    {
        $category = $data['category'] ?? null;

        return array_values(array_filter(
            BlockData::partners(),
            fn (array $partner): bool => $category === null || $category === '' || $partner['category'] === $category,
        ));
    }
}
