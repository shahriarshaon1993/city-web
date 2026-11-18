<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\TagRowResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-08 tags: name and the number of posts that carry each tag (any status).
 *
 * @extends AdminTable<Tag>
 */
final class TagsTable extends AdminTable
{
    public const string KEY = 'tags';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['news.view'];
    }

    public function url(): string
    {
        return route('admin.news.tags.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Tag::query()->withCount('posts');
    }

    protected function filters(): array
    {
        return [];
    }

    protected function sorts(): array
    {
        return ['name', 'posts_count', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    protected function resource(): string
    {
        return TagRowResource::class;
    }

    protected function defaultPerPage(): int
    {
        return 50;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Tag', 'value' => fn (Tag $tag): string => $tag->name],
            'slug' => ['label' => 'URL', 'value' => fn (Tag $tag): string => $tag->path()],
            'posts_count' => ['label' => 'Posts', 'value' => fn (Tag $tag): int => (int) $tag->getAttribute('posts_count')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return [];
    }
}
