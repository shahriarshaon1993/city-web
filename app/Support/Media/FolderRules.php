<?php

namespace App\Support\Media;

use App\Models\MediaAsset;
use App\Models\MediaFolder;
use Illuminate\Validation\ValidationException;

/**
 * Shared folder checks (CMS-MED-002): sibling names are unique ignoring case (MySQL's unique key
 * misses root folders, whose parent is NULL), and only an empty folder may be deleted.
 */
final class FolderRules
{
    public static function cleanName(string $name): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $name)), 0, MediaFolder::NAME_MAX);
    }

    /**
     * @throws ValidationException
     */
    public static function ensureNameIsFree(string $name, ?int $parentId, ?int $ignoreId = null, string $field = 'name'): void
    {
        $taken = MediaFolder::query()
            ->where('parent_id', $parentId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                $field => $parentId === null
                    ? "A folder called “{$name}” already exists at the top level."
                    : "This folder already has a subfolder called “{$name}”.",
            ]);
        }
    }

    /** Why the folder cannot be deleted now, or null (HELP-08 tooltip, DEL-03). */
    public static function deleteBlock(MediaFolder $folder, ?int $children = null, ?int $files = null): ?string
    {
        $children ??= MediaFolder::query()->where('parent_id', $folder->id)->count();
        $files ??= MediaAsset::query()->where('folder_id', $folder->id)->count();

        if ($children === 0 && $files === 0) {
            return null;
        }

        $parts = array_filter([
            $files > 0 ? ($files === 1 ? '1 file' : "{$files} files") : null,
            $children > 0 ? ($children === 1 ? '1 subfolder' : "{$children} subfolders") : null,
        ]);

        return '“'.$folder->name.'” still holds '.implode(' and ', $parts).'. Move or delete them first; only an empty folder can be deleted.';
    }
}
