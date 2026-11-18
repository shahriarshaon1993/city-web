<?php

namespace App\Actions\News;

use App\Models\Tag;
use Illuminate\Support\Str;

/**
 * FR-NEWS-003: tags are free entry. A name that matches an existing tag case-insensitively is that
 * tag (the column collation compares that way), otherwise a tag with a free slug is created.
 */
final class ResolveTags
{
    /**
     * @param  list<string>  $names  as typed in the editor
     * @return list<Tag> one per distinct name, in the order given
     */
    public function handle(array $names): array
    {
        $tags = [];

        foreach ($names as $name) {
            $name = trim((string) preg_replace('/\s+/u', ' ', (string) $name));

            if ($name === '') {
                continue;
            }

            $tag = Tag::query()->where('name', $name)->first() ?? Tag::query()->create(['name' => $name, 'slug' => $this->freeSlug($name)]);
            $tags[$tag->id] = $tag;
        }

        return array_values($tags);
    }

    public function freeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tag';
        $candidate = $base;

        for ($number = 2; Tag::query()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-{$number}";
        }

        return $candidate;
    }
}
