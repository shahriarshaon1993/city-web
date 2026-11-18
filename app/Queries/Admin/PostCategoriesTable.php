<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\PostCategoryRowResource;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-08 categories: a small table in the order the news pages show, with the post count that
 * DEL-03 needs.
 *
 * @extends AdminTable<PostCategory>
 */
final class PostCategoriesTable extends AdminTable
{
    public const string KEY = 'post-categories';

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
        return route('admin.news.categories.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return PostCategory::query()->withCount('posts');
    }

    protected function filters(): array
    {
        return [];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'name', 'posts_count'];
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('slug', 'like', $like));
    }

    protected function resource(): string
    {
        return PostCategoryRowResource::class;
    }

    protected function defaultPerPage(): int
    {
        return 50;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Category', 'value' => fn (PostCategory $category): string => $category->name],
            'slug' => ['label' => 'URL', 'value' => fn (PostCategory $category): string => $category->path()],
            'posts_count' => ['label' => 'Posts', 'value' => fn (PostCategory $category): int => (int) $category->getAttribute('posts_count')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return [];
    }
}
