<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\ActivityRowResource;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use App\Support\Activity\ActivityAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-17 activity log. Read-only (ADM-LOG-004). `activity.view-all` sees every row;
 * `activity.view-own` only the rows the user caused (spec/admin.md §10.2).
 *
 * @extends AdminTable<Activity>
 */
final class ActivityLogTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'activity-log';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['activity.view-all', 'activity.view-own'];
    }

    public function url(): string
    {
        return route('admin.activity-log.index');
    }

    public static function canViewAll(User $actor): bool
    {
        return $actor->can('activity.view-all');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Activity::query()
            ->with([
                'causer' => fn (MorphTo $causer) => $causer->morphWith([User::class => ['roles']])->withTrashed(),
                'subject',
            ])
            ->when(! self::canViewAll($actor), fn (Builder $query) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->where('causer_id', $actor->getKey()));
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::exact('log', 'log_name'),
            AllowedFilter::exact('event'),
            AllowedFilter::callback('causer', fn (Builder $query, mixed $value) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->whereIn('causer_id', array_map('intval', Arr::wrap($value)))),
            AllowedFilter::callback('ip', fn (Builder $query, mixed $value) => $query
                ->where('properties->ip', 'like', '%'.addcslashes((string) $value, '%_\\').'%')),
            ...$this->dateRange('when', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['created_at', 'log_name', 'event', 'description'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query
            ->where('description', 'like', $like)
            ->orWhere('event', 'like', $like)
            ->orWhere('log_name', 'like', $like)
            ->orWhere('properties->ip', 'like', $like)
            ->orWhereHasMorph('causer', [User::class], fn (Builder $causer) => $causer->where('name', 'like', $like)));
    }

    protected function resource(): string
    {
        return ActivityRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'created_at' => ['label' => 'When', 'value' => fn (Activity $row): ?string => $row->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i:s')],
            'who' => ['label' => 'Who', 'value' => fn (Activity $row): string => $row->causer instanceof User ? $row->causer->name : ''],
            'action' => ['label' => 'Action', 'value' => fn (Activity $row): string => ActivityAction::for($row)['label']],
            'description' => ['label' => 'What', 'value' => fn (Activity $row): string => (string) $row->description],
            'ip' => ['label' => 'IP address', 'value' => fn (Activity $row): ?string => $row->properties['ip'] ?? null],
            'log' => ['label' => 'Area', 'value' => fn (Activity $row): string => (string) $row->log_name],
            'changes' => ['label' => 'Changes', 'value' => fn (Activity $row): string => $this->changeSummary($row)],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $counts = fn (string $column): array => $this->baseQuery($actor)
            ->reorder()
            ->whereNotNull($column)
            ->select($column)
            ->selectRaw('count(*) as total')
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->map(fn (Activity $row): array => [
                'value' => (string) $row->getAttribute($column),
                'label' => Str::headline((string) $row->getAttribute($column)),
                'count' => (int) $row->getAttribute('total'),
            ])
            ->values()
            ->all();

        return ['log' => $counts('log_name'), 'event' => $counts('event')];
    }

    private function changeSummary(Activity $row): string
    {
        $old = $row->properties['old'] ?? [];
        $new = $row->properties['attributes'] ?? [];

        return collect([...array_keys($old), ...array_keys($new)])
            ->unique()
            ->map(fn (string|int $field): string => sprintf(
                '%s: %s → %s',
                $field,
                is_scalar($old[$field] ?? null) ? $old[$field] : '–',
                is_scalar($new[$field] ?? null) ? $new[$field] : '–',
            ))
            ->implode('; ');
    }
}
