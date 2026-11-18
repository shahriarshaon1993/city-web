<?php

namespace App\Support\Blocks;

use App\Models\PageBlock;

/**
 * Finds fields inside a block's `data` through the dotted paths its schema declares (`tabs.*.image`,
 * `images.*`): the library asset ids (`BlockSchema::media`, for usages and "Replace everywhere") and
 * the rich-text fields (`BlockSchema::html`, for sanitising).
 */
final class MediaPaths
{
    /**
     * Every asset id with the concrete path it sits at (`tabs.1.image`).
     *
     * @param  array<string, mixed>  $data
     * @return list<array{path: string, id: int}>
     */
    public static function entries(BlockType $type, array $data): array
    {
        $found = [];

        foreach ($type->schema()->media() as $pattern) {
            foreach (self::expand($data, $pattern) as $entry) {
                if (is_numeric($entry['value']) && (int) $entry['value'] > 0) {
                    $found[] = ['path' => $entry['path'], 'id' => (int) $entry['value']];
                }
            }
        }

        return $found;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    public static function ids(BlockType $type, array $data): array
    {
        return array_column(self::entries($type, $data), 'id');
    }

    /**
     * The block's data with one asset swapped for another, or null when it does not use it.
     *
     * @return array<string, mixed>|null
     */
    public static function replace(PageBlock $block, int $from, int $to): ?array
    {
        $type = $block->blockType();

        if ($type === null) {
            return null;
        }

        $data = $block->data;
        $changed = false;

        foreach (self::entries($type, $data) as $entry) {
            if ($entry['id'] === $from) {
                data_set($data, $entry['path'], $to);
                $changed = true;
            }
        }

        return $changed ? $data : null;
    }

    /**
     * Every value a dotted pattern reaches, with its concrete path; `*` stands for each list item.
     *
     * @param  array<int|string, mixed>  $data
     * @return list<array{path: string, value: mixed}>
     */
    public static function expand(array $data, string $pattern): array
    {
        $found = [];
        self::walk($data, explode('.', $pattern), '', $found);

        return $found;
    }

    /**
     * @param  array<int|string, mixed>  $node
     * @param  list<string>  $segments
     * @param  list<array{path: string, value: mixed}>  $found
     */
    private static function walk(array $node, array $segments, string $prefix, array &$found): void
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return;
        }

        $keys = $segment === '*' ? array_keys($node) : [$segment];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $node)) {
                continue;
            }

            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if ($segments === []) {
                $found[] = ['path' => $path, 'value' => $node[$key]];
            } elseif (is_array($node[$key])) {
                self::walk($node[$key], $segments, $path, $found);
            }
        }
    }
}
