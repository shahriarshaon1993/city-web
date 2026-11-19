<?php

namespace App\Queries\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Resources\Admin\JobApplicationRowResource;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-10 applications list (FR-CAR-005): the pipeline status, the role (or "General CV"), a "needs
 * review" flag and the day received, newest first. Everything here is personal data, so the whole table
 * needs `applications.manage`, the export included.
 *
 * @extends AdminTable<JobApplication>
 */
final class JobApplicationsTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'job_applications';

    /** The `job` filter value for applications with no role (the general CV drop). */
    public const string GENERAL = 'general';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['applications.manage'];
    }

    public function url(): string
    {
        return route('admin.applications.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return JobApplication::query()->with('job:id,title');
    }

    protected function filters(): array
    {
        $statuses = array_map(fn (ApplicationStatus $case): string => $case->value, ApplicationStatus::cases());

        return [
            AllowedFilter::callback('status', function (Builder $query, mixed $value) use ($statuses): void {
                $values = array_values(array_intersect(array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value)), $statuses));

                if ($values !== []) {
                    $query->whereIn('status', $values);
                }
            }),
            AllowedFilter::callback('job', function (Builder $query, mixed $value): void {
                $values = array_map(fn (mixed $item): string => (string) $item, Arr::wrap($value));
                $ids = array_values(array_filter(array_map(intval(...), array_diff($values, [self::GENERAL]))));

                $query->where(function (Builder $inner) use ($values, $ids): void {
                    if ($ids !== []) {
                        $inner->whereIn('job_post_id', $ids);
                    }

                    if (in_array(self::GENERAL, $values, true)) {
                        $inner->orWhereNull('job_post_id');
                    }
                });
            }),
            AllowedFilter::callback('review', function (Builder $query, mixed $value): void {
                if (in_array($value, ['1', 1, true, 'true'], true)) {
                    $query->whereNotNull('review_reason');
                }
            }),
            ...$this->dateRange('received', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['name', 'status', 'years_experience', 'created_at', 'updated_at'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->orWhere('current_position', 'like', $like)
            ->orWhereHas('job', fn (Builder $job) => $job->where('title', 'like', $like)));
    }

    protected function resource(): string
    {
        return JobApplicationRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Name', 'value' => fn (JobApplication $application): string => $application->name],
            'email' => ['label' => 'Email', 'value' => fn (JobApplication $application): string => $application->email],
            'phone' => ['label' => 'Phone', 'value' => fn (JobApplication $application): string => $application->phone, 'text' => true],
            'job' => ['label' => 'Role', 'value' => fn (JobApplication $application): string => $application->job->title ?? 'General CV'],
            'status' => ['label' => 'Status', 'value' => fn (JobApplication $application): string => $application->status->label()],
            'current_position' => ['label' => 'Current position', 'value' => fn (JobApplication $application): ?string => $application->current_position],
            'years_experience' => ['label' => 'Years of experience', 'value' => fn (JobApplication $application): ?int => $application->years_experience],
            'expected_salary' => ['label' => 'Expected salary', 'value' => fn (JobApplication $application): ?string => $application->expected_salary],
            'linkedin_url' => ['label' => 'LinkedIn', 'value' => fn (JobApplication $application): ?string => $application->linkedin_url],
            'created_at' => ['label' => 'Received', 'value' => fn (JobApplication $application): ?string => $application->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $statuses = JobApplication::query()->toBase()->select('status')->selectRaw('count(*) as total')->whereNull('deleted_at')->groupBy('status')->pluck('total', 'status');
        $jobs = JobApplication::query()->toBase()->select('job_post_id')->selectRaw('count(*) as total')->whereNull('deleted_at')->groupBy('job_post_id')->pluck('total', 'job_post_id');

        $roles = JobPost::withTrashed()->whereIn('id', $jobs->keys()->filter()->all())->orderBy('title')->get(['id', 'title'])
            ->map(fn (JobPost $job): array => ['value' => (string) $job->id, 'label' => $job->title, 'count' => (int) $jobs[$job->id]])
            ->all();

        if (isset($jobs[''])) {
            $roles[] = ['value' => self::GENERAL, 'label' => 'General CV', 'count' => (int) $jobs['']];
        }

        return [
            'status' => array_map(fn (ApplicationStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($statuses[$case->value] ?? 0),
            ], ApplicationStatus::cases()),
            'job' => $roles,
        ];
    }
}
