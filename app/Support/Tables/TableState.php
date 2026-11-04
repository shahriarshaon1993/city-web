<?php

namespace App\Support\Tables;

use Illuminate\Http\Request;

/**
 * The server-relevant part of a table's URL state (ADM-TBL-001..004, 011): page,
 * per_page, sorts, filters and the global search. Everything is sanitised here
 * so a hand-edited URL never reaches the query builder with an unknown column
 * (columns, order, widths and density are client state and only matter for export).
 */
final readonly class TableState
{
    /** ADM-TBL-001 */
    public const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const int DEFAULT_PER_PAGE = 25;

    /** ADM-TBL-002 */
    public const int MAX_SORTS = 3;

    public const int MAX_SEARCH_LENGTH = 100;

    /**
     * @param  list<string>  $sorts  effective sort, `-` prefix = descending
     * @param  array<string, string>  $filters  filter name → comma-separated value(s)
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public array $sorts,
        public array $filters,
        public string $search,
    ) {}

    /**
     * @param  list<string>  $allowedSorts
     * @param  list<string>  $allowedFilters
     */
    public static function fromRequest(Request $request, array $allowedSorts, array $allowedFilters, string $defaultSort): self
    {
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);

        $sorts = [];
        $seen = [];

        foreach (explode(',', (string) $request->input('sort', '')) as $sort) {
            $column = ltrim(trim($sort), '-');

            if ($column !== '' && in_array($column, $allowedSorts, true) && ! isset($seen[$column])) {
                $seen[$column] = true;
                $sorts[] = str_starts_with(trim($sort), '-') ? '-'.$column : $column;
            }
        }

        $filters = [];
        $rawFilters = $request->input('filter', []);

        foreach (is_array($rawFilters) ? $rawFilters : [] as $name => $value) {
            if (! in_array($name, $allowedFilters, true)) {
                continue;
            }

            $value = is_array($value)
                ? implode(',', array_filter($value, is_scalar(...)))
                : (is_scalar($value) ? (string) $value : '');

            if (trim($value) !== '') {
                $filters[$name] = trim($value);
            }
        }

        return new self(
            page: max(1, (int) $request->input('page', 1)),
            perPage: in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE,
            sorts: array_slice($sorts, 0, self::MAX_SORTS) ?: [$defaultSort],
            filters: $filters,
            search: mb_substr(trim((string) $request->input('search', '')), 0, self::MAX_SEARCH_LENGTH),
        );
    }

    /**
     * The `filter` and `sort` parameters spatie/laravel-query-builder reads.
     *
     * @return array{filter: array<string, string>, sort: string}
     */
    public function toQueryBuilderParameters(): array
    {
        return [
            'filter' => $this->search === '' ? $this->filters : [...$this->filters, 'search' => $this->search],
            'sort' => implode(',', $this->sorts),
        ];
    }
}
