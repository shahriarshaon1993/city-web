<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 23, CMP-BLK-AWARDS: heading, 1–24 items (image*, title*, issuer, year); FR-SOC-006. */
final class AwardsSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'items' => ['required', 'array', 'min:1', 'max:24'],
            'items.*.image' => BlockRules::image(true),
            'items.*.title' => BlockRules::text(80, true),
            'items.*.issuer' => BlockRules::text(80),
            'items.*.year' => ['nullable', 'integer', 'between:1950,2100'],
        ];
    }

    public function media(): array
    {
        return ['items.*.image'];
    }

    public function attributes(): array
    {
        return ['items' => 'awards', 'items.*.image' => 'award image', 'items.*.title' => 'award title', 'items.*.issuer' => 'issuer', 'items.*.year' => 'year'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $items = [];

        foreach ($data['items'] ?? [] as $item) {
            $image = $context->image($item['image'] ?? null);

            if ($image !== null) {
                $items[] = [
                    'image' => $image,
                    'title' => (string) ($item['title'] ?? ''),
                    'issuer' => ($item['issuer'] ?? '') === '' ? null : $item['issuer'],
                    'year' => ($item['year'] ?? null) === null ? null : (int) $item['year'],
                ];
            }
        }

        return ['heading' => $data['heading'] ?? null, 'items' => $items];
    }
}
