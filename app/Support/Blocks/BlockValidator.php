<?php

namespace App\Support\Blocks;

use App\Models\PageBlock;
use Illuminate\Support\Facades\Validator;

/**
 * CMS-PB-003: validates a block's stored data with its type's schema. A block that fails is still
 * saved (draft only, flagged red); the publish checklist refuses to publish while any block fails.
 */
final class BlockValidator
{
    /**
     * Messages by path inside `data` (`items.2.title`); empty when the block is valid.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, list<string>>
     */
    public static function errors(BlockType $type, array $data): array
    {
        $schema = $type->schema();
        $validator = Validator::make($data, $schema->rules(), $schema->messages(), $schema->attributes());

        $validator->after(function ($validator) use ($schema, $data): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($schema->checks($data) as $path => $message) {
                $validator->errors()->add($path, $message);
            }
        });

        return $validator->fails() ? array_map(array_values(...), $validator->errors()->messages()) : [];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function forBlock(PageBlock $block): array
    {
        $type = $block->blockType();

        return $type === null ? ['type' => ['Unknown block type “'.$block->type.'”.']] : self::errors($type, $block->data);
    }

    /** The number of messages (what the red "n errors" badge shows). */
    public static function count(PageBlock $block): int
    {
        return array_sum(array_map(count(...), self::forBlock($block)));
    }
}
