<?php

namespace App\Queries\Admin;

use App\Models\SavedTableView;
use App\Models\User;
use App\Support\Tables\TableState;
use Closure;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\LazyCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Server side of one admin DataTable (ADM-TBL, DEC-34): the allowlisted
 * filters/sorts, the search, the row Resource and the export columns. A
 * subclass per list; the controller only calls `props()`. Every request value
 * goes through TableState first, so unknown columns are dropped, never queried.
 *
 * @template TModel of Model
 */
abstract class AdminTable
{
    /** Table key stored in DM-VIEW `table_key` (≤ 40 characters). */
    abstract public function key(): string;

    /**
     * Permissions of which the user needs at least one to open the table.
     *
     * @return list<string>
     */
    abstract public function permissions(): array;

    /** The list page that shows this table (saved views redirect back to it). */
    abstract public function url(): string;

    final public function isVisibleTo(User $user): bool
    {
        return $user->canAny($this->permissions());
    }

    /**
     * Rows the actor may see at all (permission scoping happens here).
     *
     * @return Builder<TModel>
     */
    abstract protected function baseQuery(User $actor): Builder;

    /**
     * @return list<AllowedFilter>
     */
    abstract protected function filters(): array;

    /**
     * @return list<string>
     */
    abstract protected function sorts(): array;

    /** Default sort, `-` prefix = descending. */
    abstract protected function defaultSort(): string;

    /**
     * @param  Builder<Model>  $query
     */
    abstract protected function applySearch(Builder $query, string $term): void;

    /**
     * @return class-string<JsonResource>
     */
    abstract protected function resource(): string;

    /**
     * ADM-TBL-012: export column id → label and value (the client sends the visible ids in order).
     *
     * @return array<string, array{label: string, value: Closure(TModel): (scalar|null)}>
     */
    abstract public function exportColumns(): array;

    /**
     * Option lists with counts for enum filters (ADM-TBL-003).
     *
     * @return array<string, array<int, array{value: string, label: string, count: int}>>
     */
    public function filterOptions(User $actor): array
    {
        return [];
    }

    final public function state(Request $request): TableState
    {
        return TableState::fromRequest(
            $request,
            $this->sorts(),
            array_map(fn (AllowedFilter $filter): string => $filter->getName(), $this->filters()),
            $this->defaultSort(),
        );
    }

    /**
     * @return QueryBuilder<TModel>
     */
    final public function query(TableState $state, Request $request, User $actor): QueryBuilder
    {
        $builder = QueryBuilder::for(
            $this->baseQuery($actor),
            $request->duplicate(query: $state->toQueryBuilderParameters()),
        )
            ->allowedFilters(...[
                ...$this->filters(),
                AllowedFilter::callback('search', fn (Builder $query, mixed $term) => $this->applySearch($query, (string) $term)),
            ])
            ->allowedSorts(...$this->sorts());

        // Stable paging when the chosen sort has ties.
        $builder->orderByDesc($builder->getEloquentBuilder()->getModel()->getQualifiedKeyName());

        return $builder;
    }

    /**
     * Inertia props of the list: `rows` and `meta` are what a table refetch asks for.
     *
     * @return array<string, mixed>
     */
    final public function props(Request $request, User $actor): array
    {
        $state = $this->state($request);
        $query = $this->query($state, $request, $actor);

        $paginator = $query->paginate($state->perPage, ['*'], 'page', $state->page);

        if ($paginator->isEmpty() && $state->page > 1 && $paginator->lastPage() < $state->page) {
            $paginator = $query->paginate($state->perPage, ['*'], 'page', max(1, $paginator->lastPage()));
        }

        $resource = $this->resource();

        return [
            'rows' => $resource::collection($paginator->items())->resolve($request),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'sort' => $state->sorts,
            ],
            'views' => fn (): array => $this->views($actor),
            'filterOptions' => fn (): array => $this->filterOptions($actor),
        ];
    }

    /**
     * Every row of the filtered, sorted state (no paging), streamed for the export.
     *
     * @return LazyCollection<int, TModel>
     */
    final public function lazyRows(Request $request, User $actor): LazyCollection
    {
        return $this->query($this->state($request), $request, $actor)->lazy(500);
    }

    /**
     * Export columns the client asked for, in its order; unknown ids drop out, none left = all.
     *
     * @param  list<string>  $ids
     * @return list<string>
     */
    final public function exportColumnIds(array $ids): array
    {
        $available = $this->exportColumns();
        $known = array_values(array_filter($ids, fn (string $id): bool => isset($available[$id])));

        return $known === [] ? array_keys($available) : $known;
    }

    /**
     * @param  list<string>  $columnIds  from exportColumnIds()
     * @return list<string>
     */
    final public function exportHeader(array $columnIds): array
    {
        $available = $this->exportColumns();

        return array_map(fn (string $id): string => $available[$id]['label'], $columnIds);
    }

    /**
     * One list of cell values per row of the filtered, sorted state.
     *
     * @param  list<string>  $columnIds  from exportColumnIds()
     * @return Generator<int, list<scalar|null>>
     */
    final public function exportRows(Request $request, User $actor, array $columnIds): Generator
    {
        $available = $this->exportColumns();

        foreach ($this->lazyRows($request, $actor) as $row) {
            yield array_map(fn (string $id): mixed => $available[$id]['value']($row), $columnIds);
        }
    }

    final public function count(Request $request, User $actor): int
    {
        return $this->query($this->state($request), $request, $actor)->getEloquentBuilder()->toBase()->getCountForPagination();
    }

    /**
     * ADM-TBL-009 "select all {n} matching": the rows named by `ids`, or, with `all`, every row of
     * the posted filter state. Never wider than what the actor may see.
     *
     * @return Builder<TModel>
     */
    final public function selection(Request $request, User $actor): Builder
    {
        if ($request->boolean('all')) {
            return $this->query($this->state($request), $request, $actor)->getEloquentBuilder()->reorder();
        }

        $ids = array_values(array_filter((array) $request->input('ids', []), is_scalar(...)));

        return $this->baseQuery($actor)->whereKey($ids);
    }

    /**
     * ADM-TBL-010: a bare `/admin/x` opens the user's default view; `?view={id}` alone expands that
     * view into its full URL state. The URL stays the single source of the table state.
     */
    final public function viewRedirect(Request $request, User $actor): ?string
    {
        $query = $request->query();
        $viewId = $request->query('view');

        if ($query === [] || ($viewId !== null && array_keys($query) === ['view'])) {
            $view = $viewId !== null
                ? $this->visibleViews($actor)->whereKey((int) $viewId)->first()
                : $this->visibleViews($actor)->where('user_id', $actor->id)->where('is_default', true)->first();

            if ($view !== null) {
                return $request->url().'?'.http_build_query([...$view->state, 'view' => $view->id]);
            }
        }

        return null;
    }

    /**
     * @return Builder<SavedTableView>
     */
    private function visibleViews(User $actor): Builder
    {
        return SavedTableView::query()
            ->where('table_key', $this->key())
            ->where(fn (Builder $query) => $query->where('user_id', $actor->id)->orWhere('is_shared', true));
    }

    /**
     * @return array<int, array{id: int, name: string, is_shared: bool, is_default: bool, mine: bool, owner: string, state: array<string, mixed>}>
     */
    private function views(User $actor): array
    {
        return $this->visibleViews($actor)
            ->with('user:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (SavedTableView $view): array => [
                'id' => $view->id,
                'name' => $view->name,
                'is_shared' => $view->is_shared,
                'is_default' => $view->is_default && $view->user_id === $actor->id,
                'mine' => $view->user_id === $actor->id,
                'owner' => (string) $view->user?->name,
                'state' => $view->state,
            ])
            ->values()
            ->all();
    }
}
