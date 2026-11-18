<?php

namespace App\Support\Blocks\Schemas;

use App\Enums\Segment;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Search\ProjectFilters;
use Illuminate\Validation\Rule;

/** Block 6, CMP-BLK-SEGMENTS: 2–4 tiles, each a project type with a photo (≥ 1200 px) that links to its facet page. */
final class SegmentTilesSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'tiles' => ['required', 'array', 'min:2', 'max:4'],
            'tiles.*.segment' => ['required', 'string', 'distinct', Rule::enum(Segment::class)],
            'tiles.*.image' => BlockRules::image(true, 1200),
            'tiles.*.label' => BlockRules::text(40),
            'tiles.*.description' => BlockRules::text(120),
        ];
    }

    public function media(): array
    {
        return ['tiles.*.image'];
    }

    public function attributes(): array
    {
        return ['tiles' => 'tiles', 'tiles.*.segment' => 'project type', 'tiles.*.image' => 'tile image', 'tiles.*.label' => 'tile label', 'tiles.*.description' => 'tile description'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $tiles = [];

        foreach ($data['tiles'] ?? [] as $tile) {
            $segment = Segment::tryFrom((string) ($tile['segment'] ?? ''));

            if ($segment === null) {
                continue;
            }

            $tiles[] = [
                'segment' => $segment->value,
                'label' => ($tile['label'] ?? '') !== '' ? $tile['label'] : $segment->label(),
                'description' => $tile['description'] ?? null,
                'image' => $context->image($tile['image'] ?? null),
                'url' => (string) ProjectFilters::landingUrl('segment', $segment->value),
            ];
        }

        return ['heading' => $data['heading'] ?? null, 'tiles' => $tiles];
    }
}
