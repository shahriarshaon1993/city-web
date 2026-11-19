<?php

namespace App\Queries\Admin;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Http\Resources\Admin\JobPostRowResource;
use App\Models\JobPost;
use App\Models\Office;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-10 jobs list: status, department, office and type filters, search over title, department and
 * slug, soonest deadline first among open roles. The Draft / Open / Closed tabs of CRUD-02 are the
 * `status` filter.
 *
 * @extends AdminTable<JobPost>
 */
final class JobPostsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'job_posts';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['jobs.view'];
    }

    public function url(): string
    {
        return route('admin.careers.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return JobPost::query()->with('office:id,name')->withCount([
            'applications',
            'applications as new_applications_count' => fn (Builder $query) => $query->where('status', 'new'),
        ]);
    }

    protected function filters(): array
    {
        return [
            $this->listFilter('status', 'status', array_map(fn (JobStatus $case): string => $case->value, JobStatus::cases())),
            $this->listFilter('type', 'employment_type', array_map(fn (EmploymentType $case): string => $case->value, EmploymentType::cases())),
            AllowedFilter::callback('department', function (Builder $query, mixed $value): void {
                $values = array_values(array_filter(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value))));

                if ($values !== []) {
                    $query->whereIn('department', $values);
                }
            }),
            AllowedFilter::callback('office', function (Builder $query, mixed $value): void {
                $ids = array_values(array_filter(array_map(intval(...), Arr::wrap($value))));

                if ($ids !== []) {
                    $query->whereIn('office_id', $ids);
                }
            }),
            ...$this->dateRange('deadline', 'deadline'),
            ...$this->dateRange('updated', 'updated_at'),
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    private function listFilter(string $name, string $column, array $allowed): AllowedFilter
    {
        return AllowedFilter::callback($name, function (Builder $query, mixed $value) use ($column, $allowed): void {
            $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $allowed));

            if ($values !== []) {
                $query->whereIn($column, $values);
            }
        });
    }

    protected function sorts(): array
    {
        return ['title', 'department', 'status', 'deadline', 'applications_count', 'updated_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-updated_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('department', 'like', $like));
    }

    protected function resource(): string
    {
        return JobPostRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'title' => ['label' => 'Role', 'value' => fn (JobPost $job): string => $job->title],
            'slug' => ['label' => 'URL', 'value' => fn (JobPost $job): string => $job->path()],
            'department' => ['label' => 'Department', 'value' => fn (JobPost $job): string => $job->department],
            'office' => ['label' => 'Location', 'value' => fn (JobPost $job): string => (string) $job->office?->name],
            'type' => ['label' => 'Type', 'value' => fn (JobPost $job): string => $job->employment_type->label()],
            'status' => ['label' => 'Status', 'value' => fn (JobPost $job): string => $job->status->label()],
            'deadline' => ['label' => 'Deadline', 'value' => fn (JobPost $job): string => $job->deadline->format('d M Y')],
            'applications_count' => ['label' => 'Applications', 'value' => fn (JobPost $job): int => (int) $job->getAttribute('applications_count')],
            'updated_at' => ['label' => 'Updated', 'value' => fn (JobPost $job): ?string => $job->updated_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $count = fn (string $column) => JobPost::query()->toBase()->select($column)->selectRaw('count(*) as total')->whereNull('deleted_at')->groupBy($column)->pluck('total', $column);
        $statuses = $count('status');
        $types = $count('employment_type');
        $departments = $count('department');
        $offices = $count('office_id');

        return [
            'status' => array_map(fn (JobStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($statuses[$case->value] ?? 0),
            ], JobStatus::cases()),
            'type' => array_map(fn (EmploymentType $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($types[$case->value] ?? 0),
            ], EmploymentType::cases()),
            'department' => $departments->map(fn (int $total, string $department): array => [
                'value' => $department,
                'label' => $department,
                'count' => $total,
            ])->values()->all(),
            'office' => Office::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])->map(fn (Office $office): array => [
                'value' => (string) $office->id,
                'label' => $office->name,
                'count' => (int) ($offices[$office->id] ?? 0),
            ])->all(),
        ];
    }
}
