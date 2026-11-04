<?php

namespace App\Queries\Admin\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Throwable;

/**
 * ADM-TBL-003 date range: two filters (`{name}_from`, `{name}_to`, `YYYY-MM-DD`) that cover whole
 * days in Dhaka time, since admin dates are shown there (design/admin.md §12.3).
 */
trait FiltersByDate
{
    /**
     * @return list<AllowedFilter>
     */
    protected function dateRange(string $name, string $column): array
    {
        return [
            AllowedFilter::callback("{$name}_from", function (Builder $query, mixed $value) use ($column): void {
                $start = $this->dhakaDay($value)?->startOfDay();

                if ($start !== null) {
                    $query->where($column, '>=', $start->utc());
                }
            }),
            AllowedFilter::callback("{$name}_to", function (Builder $query, mixed $value) use ($column): void {
                $end = $this->dhakaDay($value)?->endOfDay();

                if ($end !== null) {
                    $query->where($column, '<=', $end->utc());
                }
            }),
        ];
    }

    private function dhakaDay(mixed $value): ?CarbonImmutable
    {
        try {
            return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
                ? CarbonImmutable::createFromFormat('!Y-m-d', $value, 'Asia/Dhaka')
                : null;
        } catch (Throwable) {
            return null;
        }
    }
}
