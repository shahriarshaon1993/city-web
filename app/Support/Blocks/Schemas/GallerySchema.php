<?php

namespace App\Support\Blocks\Schemas;

use App\Models\MediaAsset;
use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;

/** Block 21, CMP-BLK-GALLERY: heading, 2–40 library images (alt text required), grid or masonry. */
final class GallerySchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'layout' => BlockRules::choice(['grid', 'masonry']),
            'images' => ['required', 'array', 'min:2', 'max:40'],
            'images.*' => [...BlockRules::image(true), 'distinct'],
        ];
    }

    public function media(): array
    {
        return ['images.*'];
    }

    public function attributes(): array
    {
        return ['images' => 'images', 'images.*' => 'image'];
    }

    public function checks(array $data): array
    {
        $ids = array_map(intval(...), $data['images'] ?? []);
        $assets = MediaAsset::query()->whereIn('id', $ids)->get()->keyBy('id');
        $errors = [];

        foreach ($ids as $index => $id) {
            $asset = $assets->get($id);

            if ($asset !== null && ! $asset->is_decorative && trim((string) $asset->alt) === '') {
                $errors["images.{$index}"] = 'Image '.($index + 1).' needs alt text or must be marked decorative.';
            }
        }

        return $errors;
    }

    public function resolve(array $data, BlockContext $context): array
    {
        return [
            'heading' => $data['heading'] ?? null,
            'layout' => $data['layout'] ?? 'grid',
            'images' => $context->images($data['images'] ?? []),
        ];
    }
}
