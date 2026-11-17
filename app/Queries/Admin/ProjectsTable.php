<?php

namespace App\Queries\Admin;

use App\Enums\BookingState;
use App\Enums\Lifecycle;
use App\Enums\PublishStatus;
use App\Enums\Segment;
use App\Http\Resources\Admin\ProjectRowResource;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-01 projects list (design/admin.md §12.3). The "Leads (30d)" column waits for the Leads
 * module (15.x). The columns of the export are the ones of the list.
 *
 * @extends AdminTable<Project>
 */
final class ProjectsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'projects';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['projects.view'];
    }

    public function url(): string
    {
        return route('admin.projects.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Project::query()->with(['cover.media', 'location.parent', 'pendingRevision:id,publish_at']);
    }

    protected function filters(): array
    {
        return [
            $this->enumFilter('segment', 'segment', Segment::class),
            $this->enumFilter('lifecycle', 'lifecycle', Lifecycle::class),
            $this->enumFilter('booking', 'booking_state', BookingState::class),
            $this->enumFilter('status', 'publish_status', PublishStatus::class),
            AllowedFilter::callback('area', fn (Builder $query, mixed $value) => $query->whereIn('location_id', array_map(intval(...), Arr::wrap($value)))),
            AllowedFilter::callback('featured', function (Builder $query, mixed $value): void {
                $values = array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value));

                if (in_array('1', $values, true) xor in_array('0', $values, true)) {
                    $query->where('is_featured', in_array('1', $values, true));
                }
            }),
            ...$this->dateRange('updated', 'updated_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['name', 'segment', 'lifecycle', 'booking_state', 'publish_status', 'is_featured', 'updated_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-updated_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('tagline', 'like', $like));
    }

    protected function resource(): string
    {
        return ProjectRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Project', 'value' => fn (Project $project): string => $project->name],
            'slug' => ['label' => 'URL', 'value' => fn (Project $project): string => "/projects/{$project->slug}"],
            'segment' => ['label' => 'Segment', 'value' => fn (Project $project): string => $project->segment->label()],
            'lifecycle' => ['label' => 'Status', 'value' => fn (Project $project): string => $project->lifecycle->label()],
            'booking_state' => ['label' => 'Booking', 'value' => fn (Project $project): string => $project->booking_state->label() ?? ''],
            'area' => ['label' => 'Area', 'value' => fn (Project $project): string => $project->location->name ?? ''],
            'publish_status' => ['label' => 'Publishing', 'value' => fn (Project $project): string => $project->publish_status->label()],
            'is_featured' => ['label' => 'Featured', 'value' => fn (Project $project): string => $project->is_featured ? 'Yes' : 'No'],
            'updated_at' => ['label' => 'Updated', 'value' => fn (Project $project): ?string => $project->updated_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $counts = fn (string $column): array => Project::query()->select($column)->selectRaw('count(*) as total')->groupBy($column)->pluck('total', $column)->all();
        /** @param  class-string<Segment|Lifecycle|BookingState|PublishStatus>  $enum */
        $options = function (string $enum, string $column) use ($counts): array {
            $totals = $counts($column);
            $items = [];

            foreach ($enum::cases() as $case) {
                $items[] = ['value' => $case->value, 'label' => $case->label() ?? 'None', 'count' => (int) ($totals[$case->value] ?? 0)];
            }

            return $items;
        };

        $areas = Project::query()->selectRaw('location_id, count(*) as total')->groupBy('location_id')->pluck('total', 'location_id');

        return [
            'segment' => $options(Segment::class, 'segment'),
            'lifecycle' => $options(Lifecycle::class, 'lifecycle'),
            'booking' => $options(BookingState::class, 'booking_state'),
            'status' => $options(PublishStatus::class, 'publish_status'),
            'area' => Location::query()->areas()->with('parent:id,name')->orderBy('name')->get()
                ->map(fn (Location $area): array => ['value' => (string) $area->id, 'label' => $area->name, 'hint' => $area->parent?->name, 'count' => (int) ($areas[$area->id] ?? 0)])
                ->all(),
            'featured' => [
                ['value' => '1', 'label' => 'Featured', 'count' => Project::query()->where('is_featured', true)->count()],
                ['value' => '0', 'label' => 'Not featured', 'count' => Project::query()->where('is_featured', false)->count()],
            ],
        ];
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    private function enumFilter(string $name, string $column, string $enum): AllowedFilter
    {
        $allowed = array_map(fn (BackedEnum $case): string => (string) $case->value, $enum::cases());

        return AllowedFilter::callback($name, function (Builder $query, mixed $value) use ($column, $allowed): void {
            $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $allowed));

            if ($values !== []) {
                $query->whereIn($column, $values);
            }
        });
    }
}
