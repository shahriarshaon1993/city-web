<?php

namespace App\Queries\Admin;

use App\Enums\PublishStatus;
use App\Http\Resources\Admin\PageRowResource;
use App\Models\Page;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-06 pages list. A user sees the builder pages with `pages.view` and the legal pages with
 * `legal.view` (PagePolicy); system pages are listed with a lock.
 *
 * @extends AdminTable<Page>
 */
final class PagesTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'pages';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['pages.view', 'legal.view'];
    }

    public function url(): string
    {
        return route('admin.pages.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Page::query()
            ->withCount('blocks')
            ->with('pendingRevision:id,publish_at')
            ->where(function (Builder $query) use ($actor): void {
                $query->when($actor->can('pages.view'), fn (Builder $builder) => $builder->orWhere('template', Page::BUILDER))
                    ->when($actor->can('legal.view'), fn (Builder $builder) => $builder->orWhere('template', Page::LEGAL));
            });
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
            AllowedFilter::callback('template', function (Builder $query, mixed $value): void {
                $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), [Page::BUILDER, Page::LEGAL]));

                if ($values !== []) {
                    $query->whereIn('template', $values);
                }
            }),
            ...$this->dateRange('updated', 'updated_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['title', 'template', 'publish_status', 'blocks_count', 'updated_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'title';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('slug', 'like', $like));
    }

    protected function resource(): string
    {
        return PageRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'title' => ['label' => 'Page', 'value' => fn (Page $page): string => $page->title],
            'slug' => ['label' => 'URL', 'value' => fn (Page $page): string => $page->path()],
            'template' => ['label' => 'Template', 'value' => fn (Page $page): string => $page->isLegal() ? 'Legal' : 'Builder'],
            'blocks_count' => ['label' => 'Blocks', 'value' => fn (Page $page): int => (int) $page->blocks_count],
            'publish_status' => ['label' => 'Publishing', 'value' => fn (Page $page): string => $page->publish_status->label()],
            'updated_at' => ['label' => 'Updated', 'value' => fn (Page $page): ?string => $page->updated_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $totals = $this->baseQuery($actor)->reorder()->toBase()
            ->select('publish_status')->selectRaw('count(*) as total')->groupBy('publish_status')
            ->pluck('total', 'publish_status');

        $templates = $this->baseQuery($actor)->reorder()->toBase()
            ->select('template')->selectRaw('count(*) as total')->groupBy('template')
            ->pluck('total', 'template');

        return [
            'status' => array_map(fn (PublishStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($totals[$case->value] ?? 0),
            ], PublishStatus::cases()),
            'template' => [
                ['value' => Page::BUILDER, 'label' => 'Builder pages', 'count' => (int) ($templates[Page::BUILDER] ?? 0)],
                ['value' => Page::LEGAL, 'label' => 'Legal pages', 'count' => (int) ($templates[Page::LEGAL] ?? 0)],
            ],
        ];
    }
}
