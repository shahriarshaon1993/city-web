<?php

namespace App\Support\Blocks;

use App\Support\Html\BasicHtml;

/**
 * NFR-SEC-03: rich-text fields of a block (the paths its schema lists in `html()`) are cleaned to the
 * allowed tags when the block is saved; everything else in `data` is stored as the editor sent it,
 * minus the client-only state the builder adds.
 */
final class BlockSanitizer
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clean(BlockType $type, array $data): array
    {
        $data = self::withoutEditorState($data);

        foreach ($type->schema()->html() as $pattern) {
            foreach (MediaPaths::expand($data, $pattern) as $entry) {
                if (is_string($entry['value'])) {
                    data_set($data, $entry['path'], BasicHtml::clean($entry['value'], rich: true));
                }
            }
        }

        return $data;
    }

    /**
     * The builder adds client-only state to what it sends: a `_key` on every repeater item and, on a
     * stored link, `link` (the state of its target, shown by the picker). Neither is stored.
     *
     * @param  array<int|string, mixed>  $node
     * @return array<int|string, mixed>
     */
    public static function withoutEditorState(array $node): array
    {
        unset($node['_key']);

        if (isset($node['link_type'])) {
            unset($node['link'], $node['key']);
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = self::withoutEditorState($value);
            }
        }

        return $node;
    }
}
