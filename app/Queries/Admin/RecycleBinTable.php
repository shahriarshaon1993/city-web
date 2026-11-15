<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\RecycleBinRowResource;
use App\Models\RecycleBinEntry;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use App\Support\RecycleBin\BinTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * DEL-04: one table across every bin type; each user sees only the types they may restore.
 *
 * @extends AdminTable<RecycleBinEntry>
 */
final class RecycleBinTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'recycle-bin';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return BinTypes::permissions();
    }

    public function url(): string
    {
        return route('admin.recycle-bin.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return RecycleBinEntry::query()->with('deletedBy')->whereIn('type', BinTypes::typesFor($actor));
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('type', fn (Builder $query, mixed $value) => $query->whereIn('type', Arr::wrap($value))),
            AllowedFilter::callback('deleted_by', fn (Builder $query, mixed $value) => $query->whereIn('deleted_by', array_filter(Arr::wrap($value), is_numeric(...)))),
            ...$this->dateRange('deleted', 'deleted_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['label', 'type', 'deleted_at', 'purge_at'];
    }

    protected function defaultSort(): string
    {
        return '-deleted_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('label', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    protected function resource(): string
    {
        return RecycleBinRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'label' => ['label' => 'Item', 'value' => fn (RecycleBinEntry $entry): string => $entry->label],
            'type' => ['label' => 'Type', 'value' => fn (RecycleBinEntry $entry): string => $entry->typeLabel()],
            'deleted_by' => ['label' => 'Deleted by', 'value' => fn (RecycleBinEntry $entry): ?string => $entry->deletedBy?->name],
            'deleted_at' => ['label' => 'Deleted at', 'value' => fn (RecycleBinEntry $entry): string => $entry->deleted_at->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'purge_at' => ['label' => 'Auto-delete', 'value' => fn (RecycleBinEntry $entry): string => $entry->purge_at?->timezone('Asia/Dhaka')->format('d M Y') ?? 'Never'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $types = BinTypes::typesFor($actor);

        $perType = RecycleBinEntry::query()->whereIn('type', $types)->groupBy('type')->selectRaw('type, count(*) as total')->pluck('total', 'type');
        $perDeleter = RecycleBinEntry::query()->whereIn('type', $types)->whereNotNull('deleted_by')->groupBy('deleted_by')->selectRaw('deleted_by, count(*) as total')->pluck('total', 'deleted_by');

        $typeOptions = [];

        foreach (BinTypes::options($actor) as $option) {
            $typeOptions[] = ['value' => $option['value'], 'label' => $option['label'], 'count' => (int) ($perType[$option['value']] ?? 0)];
        }

        $deleterOptions = User::query()
            ->withTrashed()
            ->whereIn('id', $perDeleter->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name, 'count' => (int) ($perDeleter[$user->id] ?? 0)])
            ->all();

        return ['type' => $typeOptions, 'deleted_by' => $deleterOptions];
    }

    /** How many items the user's bin holds, optionally of one type (the "Recycle bin (n)" links, sidebar badge). */
    public static function countFor(User $actor, ?string $type = null): int
    {
        $types = BinTypes::typesFor($actor);

        return RecycleBinEntry::query()
            ->whereIn('type', $type === null ? $types : array_intersect([$type], $types))
            ->count();
    }
}
