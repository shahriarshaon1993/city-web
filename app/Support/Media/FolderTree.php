<?php

namespace App\Support\Media;

use App\Models\MediaAsset;
use App\Models\MediaFolder;

/**
 * The folder card of the library and the picker (CMP-ADM-MEDIA): "All files", "Unfiled" and the
 * nested folders (≤ 3 levels, CMS-MED-002) with their direct file counts and, per folder, why it
 * cannot be deleted (HELP-08). Two queries, built in memory.
 *
 * @phpstan-type FolderNode array{id: int, parent_id: int|null, name: string, path: string, depth: int, count: int, delete_block: string|null, children: list<mixed>}
 */
final class FolderTree
{
    /**
     * @return array{all: int, unfiled: int, folders: list<FolderNode>}
     */
    public static function build(): array
    {
        $folders = MediaFolder::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'parent_id', 'name', 'sort_order']);

        /** Direct file count per folder id; '' = Unfiled. */
        $counts = [];

        foreach (MediaAsset::query()->selectRaw('folder_id, count(*) as total')->groupBy('folder_id')->get() as $row) {
            $counts[$row->getAttribute('folder_id') ?? ''] = (int) $row->getAttribute('total');
        }

        /** @var array<int|string, list<MediaFolder>> $byParent */
        $byParent = [];

        foreach ($folders as $folder) {
            $byParent[$folder->parent_id ?? ''][] = $folder;
        }

        return [
            'all' => array_sum($counts),
            'unfiled' => $counts[''] ?? 0,
            'folders' => self::branch($byParent, $counts, null, 1, []),
        ];
    }

    /**
     * @param  array<int|string, list<MediaFolder>>  $byParent
     * @param  array<int|string, int>  $counts
     * @param  list<string>  $path
     * @return list<FolderNode>
     */
    private static function branch(array $byParent, array $counts, ?int $parentId, int $depth, array $path): array
    {
        $nodes = [];

        foreach ($byParent[$parentId ?? ''] ?? [] as $folder) {
            $files = $counts[$folder->id] ?? 0;
            $children = count($byParent[$folder->id] ?? []);
            $trail = [...$path, $folder->name];

            $nodes[] = [
                'id' => $folder->id,
                'parent_id' => $folder->parent_id,
                'name' => $folder->name,
                'path' => implode(' › ', $trail),
                'depth' => $depth,
                'count' => $files,
                'delete_block' => FolderRules::deleteBlock($folder, $children, $files),
                'children' => self::branch($byParent, $counts, $folder->id, $depth + 1, $trail),
            ];
        }

        return $nodes;
    }
}
