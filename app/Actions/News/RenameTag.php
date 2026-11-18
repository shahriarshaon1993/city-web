<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * FR-NEWS-003: renames a tag. The public address follows the name, so the slug is recomputed
 * (kept unique); names that match another tag case-insensitively are refused by the Form Request
 * (the user is told to merge instead).
 */
final class RenameTag
{
    public function __construct(
        private readonly ResolveTags $resolveTags,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, Tag $tag, string $name): bool
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        if ($name === $tag->name) {
            return false;
        }

        $old = $tag->name;
        $slug = Str::slug($name) ?: 'tag';

        $tag->forceFill([
            'name' => $name,
            'slug' => Tag::query()->whereKeyNot($tag->id)->where('slug', $slug)->exists() ? $this->resolveTags->freeSlug($name) : $slug,
        ])->save();

        $this->logActivity->handle('news', 'updated', "Tag renamed: {$old} → {$name}", $actor, $tag, ['label' => $name, 'from' => $old]);

        return true;
    }
}
