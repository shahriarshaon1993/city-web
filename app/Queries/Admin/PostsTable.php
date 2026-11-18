<?php

namespace App\Queries\Admin;

use App\Enums\PublishStatus;
use App\Http\Resources\Admin\PostRowResource;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-08 posts list: status and category filters, search over title, excerpt and slug, newest first.
 *
 * @extends AdminTable<Post>
 */
final class PostsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'posts';

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
        return route('admin.news.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Post::query()->with(['category:id,name', 'author:id,name', 'pendingRevision:id,publish_at']);
    }

    protected function filters(): array
    {
        $statuses = array_map(fn (PublishStatus $case): string => $case->value, PublishStatus::cases());

        return [
            AllowedFilter::callback('status', function (Builder $query, mixed $value) use ($statuses): void {
                $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $statuses));

                if ($values !== []) {
                    $query->whereIn('publish_status', $values);
                }
            }),
            AllowedFilter::callback('category', function (Builder $query, mixed $value): void {
                $ids = array_values(array_filter(array_map(intval(...), Arr::wrap($value))));

                if ($ids !== []) {
                    $query->whereIn('category_id', $ids);
                }
            }),
            AllowedFilter::callback('featured', function (Builder $query, mixed $value): void {
                if (in_array($value, ['1', 1, true, 'true'], true)) {
                    $query->where('is_featured', true);
                }
            }),
            ...$this->dateRange('updated', 'updated_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['title', 'publish_status', 'published_at', 'updated_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-updated_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('excerpt', 'like', $like));
    }

    protected function resource(): string
    {
        return PostRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'title' => ['label' => 'Title', 'value' => fn (Post $post): string => $post->title],
            'slug' => ['label' => 'URL', 'value' => fn (Post $post): string => $post->path()],
            'category' => ['label' => 'Category', 'value' => fn (Post $post): string => (string) $post->category?->name],
            'author' => ['label' => 'Author', 'value' => fn (Post $post): string => (string) $post->author?->name],
            'publish_status' => ['label' => 'Publishing', 'value' => fn (Post $post): string => $post->publish_status->label()],
            'published_at' => ['label' => 'Published', 'value' => fn (Post $post): ?string => $post->published_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'updated_at' => ['label' => 'Updated', 'value' => fn (Post $post): ?string => $post->updated_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $totals = Post::query()->toBase()->select('publish_status')->selectRaw('count(*) as total')->whereNull('deleted_at')->groupBy('publish_status')->pluck('total', 'publish_status');
        $categories = Post::query()->toBase()->select('category_id')->selectRaw('count(*) as total')->whereNull('deleted_at')->groupBy('category_id')->pluck('total', 'category_id');

        return [
            'status' => array_map(fn (PublishStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($totals[$case->value] ?? 0),
            ], PublishStatus::cases()),
            'category' => PostCategory::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])->map(fn (PostCategory $category): array => [
                'value' => (string) $category->id,
                'label' => $category->name,
                'count' => (int) ($categories[$category->id] ?? 0),
            ])->all(),
        ];
    }
}
