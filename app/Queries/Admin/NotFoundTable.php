<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\NotFoundRowResource;
use App\Models\NotFoundLog;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-14 404 monitor tab (SEO-TEC-009): addresses visitors asked for that do not exist.
 *
 * @extends AdminTable<NotFoundLog>
 */
final class NotFoundTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'not-found-logs';

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
        return route('admin.not-found.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return NotFoundLog::query();
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('status', function (Builder $query, mixed $value): void {
                $statuses = Arr::wrap($value);

                if (in_array('open', $statuses, true) xor in_array('ignored', $statuses, true)) {
                    $query->where('is_ignored', in_array('ignored', $statuses, true));
                }
            }),
            AllowedFilter::callback('hits_min', fn (Builder $query, mixed $value) => is_numeric($value) ? $query->where('hits', '>=', (int) $value) : null),
            AllowedFilter::callback('hits_max', fn (Builder $query, mixed $value) => is_numeric($value) ? $query->where('hits', '<=', (int) $value) : null),
            ...$this->dateRange('last_seen', 'last_seen_at'),
            ...$this->dateRange('first_seen', 'first_seen_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['path', 'hits', 'first_seen_at', 'last_seen_at'];
    }

    protected function defaultSort(): string
    {
        return '-hits';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('path', 'like', $like)->orWhere('referrer', 'like', $like));
    }

    protected function resource(): string
    {
        return NotFoundRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'path' => ['label' => 'Address', 'value' => fn (NotFoundLog $log): string => $log->path],
            'hits' => ['label' => 'Hits', 'value' => fn (NotFoundLog $log): int => $log->hits],
            'referrer' => ['label' => 'Last referrer', 'value' => fn (NotFoundLog $log): ?string => $log->referrer],
            'first_seen_at' => ['label' => 'First seen', 'value' => fn (NotFoundLog $log): string => $log->first_seen_at->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'last_seen_at' => ['label' => 'Last seen', 'value' => fn (NotFoundLog $log): string => $log->last_seen_at->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'status' => ['label' => 'Status', 'value' => fn (NotFoundLog $log): string => $log->is_ignored ? 'Ignored' : 'Open'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return [
            'status' => [
                ['value' => 'open', 'label' => 'Open', 'count' => NotFoundLog::query()->where('is_ignored', false)->count()],
                ['value' => 'ignored', 'label' => 'Ignored', 'count' => NotFoundLog::query()->where('is_ignored', true)->count()],
            ],
        ];
    }
}
