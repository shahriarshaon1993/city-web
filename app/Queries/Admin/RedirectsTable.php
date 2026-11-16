<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\RedirectRowResource;
use App\Models\Redirect;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-14 Redirects tab. The columns of the export (Source, Target, Code, Active, …) are the ones
 * the CSV import reads back.
 *
 * @extends AdminTable<Redirect>
 */
final class RedirectsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'redirects';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['redirects.manage', 'seo.view'];
    }

    public function url(): string
    {
        return route('admin.redirects.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Redirect::query()->with('creator:id,name');
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('status', function (Builder $query, mixed $value): void {
                $statuses = Arr::wrap($value);

                if (in_array('active', $statuses, true) xor in_array('inactive', $statuses, true)) {
                    $query->where('is_active', in_array('active', $statuses, true));
                }
            }),
            AllowedFilter::callback('code', fn (Builder $query, mixed $value) => $query->whereIn('code', array_map(intval(...), Arr::wrap($value)))),
            AllowedFilter::callback('match', function (Builder $query, mixed $value): void {
                $types = Arr::wrap($value);

                if (in_array('exact', $types, true) xor in_array('prefix', $types, true)) {
                    $query->where('is_prefix', in_array('prefix', $types, true));
                }
            }),
            ...$this->dateRange('last_hit', 'last_hit_at'),
            ...$this->dateRange('created', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['source', 'target', 'code', 'is_active', 'hits', 'last_hit_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('source', 'like', $like)->orWhere('target', 'like', $like));
    }

    protected function resource(): string
    {
        return RedirectRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'source' => ['label' => 'Source', 'value' => fn (Redirect $redirect): string => $redirect->sourceLabel()],
            'target' => ['label' => 'Target', 'value' => fn (Redirect $redirect): ?string => $redirect->target],
            'code' => ['label' => 'Code', 'value' => fn (Redirect $redirect): int => $redirect->code],
            'is_active' => ['label' => 'Active', 'value' => fn (Redirect $redirect): string => $redirect->is_active ? 'Yes' : 'No'],
            'hits' => ['label' => 'Hits', 'value' => fn (Redirect $redirect): int => $redirect->hits],
            'last_hit_at' => ['label' => 'Last hit', 'value' => fn (Redirect $redirect): ?string => $redirect->last_hit_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'created_at' => ['label' => 'Added', 'value' => fn (Redirect $redirect): ?string => $redirect->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $count = fn (string $column, mixed $value): int => Redirect::query()->where($column, $value)->count();

        return [
            'status' => [
                ['value' => 'active', 'label' => 'Active', 'count' => $count('is_active', true)],
                ['value' => 'inactive', 'label' => 'Inactive', 'count' => $count('is_active', false)],
            ],
            'code' => [
                ['value' => '301', 'label' => 'Permanent (301)', 'count' => $count('code', 301)],
                ['value' => '302', 'label' => 'Temporary (302)', 'count' => $count('code', 302)],
                ['value' => '410', 'label' => 'Gone (410)', 'count' => $count('code', 410)],
            ],
            'match' => [
                ['value' => 'exact', 'label' => 'Exact address', 'count' => $count('is_prefix', false)],
                ['value' => 'prefix', 'label' => 'Everything below (prefix)', 'count' => $count('is_prefix', true)],
            ],
        ];
    }
}
