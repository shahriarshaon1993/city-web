<?php

namespace App\Queries\Admin;

use App\Enums\Labelled;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Shared parts of the lists of testimonials, partners, FAQs, team members and offices (ADM-SCR-09):
 * `social.view` opens them, rows come in the drag order, and the "Published" switch gives the
 * Published / Hidden status tabs of CRUD-02.
 *
 * @template TModel of Model
 *
 * @extends AdminTable<TModel>
 */
abstract class SocialTable extends AdminTable
{
    /**
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    public function permissions(): array
    {
        return ['social.view'];
    }

    protected function baseQuery(User $actor): Builder
    {
        return $this->model()::query();
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    protected function defaultPerPage(): int
    {
        return 50;
    }

    /** The Published / Hidden tabs; offices have no such switch and return none. */
    protected function statusFilter(): AllowedFilter
    {
        return AllowedFilter::callback('status', function (Builder $query, mixed $value): void {
            $statuses = Arr::wrap($value);

            if (in_array('published', $statuses, true) xor in_array('hidden', $statuses, true)) {
                $query->where('is_published', in_array('published', $statuses, true));
            }
        });
    }

    /**
     * @return array<int, array{value: string, label: string, count: int}>
     */
    protected function statusOptions(): array
    {
        $count = fn (bool $published): int => $this->model()::query()->where('is_published', $published)->count();

        return [
            ['value' => 'published', 'label' => 'Published', 'count' => $count(true)],
            ['value' => 'hidden', 'label' => 'Hidden', 'count' => $count(false)],
        ];
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    protected function enumFilter(string $name, string $column, string $enum): AllowedFilter
    {
        $allowed = array_map(fn (\BackedEnum $case): string => (string) $case->value, $enum::cases());

        return AllowedFilter::callback($name, function (Builder $query, mixed $value) use ($column, $allowed): void {
            $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $allowed));

            if ($values !== []) {
                $query->whereIn($column, $values);
            }
        });
    }

    /**
     * @param  class-string<\BackedEnum&Labelled>  $enum
     * @return array<int, array{value: string, label: string, count: int}>
     */
    protected function enumOptions(string $column, string $enum): array
    {
        $totals = $this->model()::query()->select($column)->selectRaw('count(*) as total')->groupBy($column)->pluck('total', $column);

        return array_map(fn (\BackedEnum&Labelled $case): array => [
            'value' => (string) $case->value,
            'label' => $case->label(),
            'count' => (int) ($totals[$case->value] ?? 0),
        ], $enum::cases());
    }

    protected function likeTerm(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
