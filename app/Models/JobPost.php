<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugHistory;
use App\Support\Careers\JobDates;
use App\Support\Media\UsesMedia;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DM-JOB: a job post (FR-CAR-001, PG-15/16). The status is Draft / Open / Closed; an Open job whose
 * deadline has passed counts as closed at once and is stored as closed by the 00:05 scheduler run
 * (FR-CAR-003). Delete = Recycle bin; a restored post comes back as a Draft (DEL-05).
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $department
 * @property int|null $office_id
 * @property EmploymentType $employment_type
 * @property string|null $experience
 * @property string|null $salary_text
 * @property int|null $salary_min_bdt
 * @property int|null $salary_max_bdt
 * @property string $summary
 * @property string|null $description
 * @property string|null $responsibilities
 * @property string|null $requirements
 * @property string|null $benefits
 * @property CarbonImmutable $deadline
 * @property JobStatus $status
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['title', 'slug', 'department', 'office_id', 'employment_type', 'experience', 'salary_text', 'salary_min_bdt', 'salary_max_bdt', 'summary', 'description', 'responsibilities', 'requirements', 'benefits', 'deadline', 'status', 'closed_at', 'published_at'])]
class JobPost extends Model implements Trashable, UsesMedia
{
    use BinBehaviour, HasSeo, HasSlugHistory, SoftDeletes;

    public const int TITLE_MAX = 120;

    public const int SUMMARY_MAX = 200;

    /** FR-CAR-003: a closed job stays reachable this many days, then 410 Gone. */
    public const int GONE_AFTER_DAYS = 30;

    /** FR-CAR-001: the department is free text; these are offered as suggestions. */
    public const array DEPARTMENT_SUGGESTIONS = ['Sales', 'Engineering', 'Architecture & design', 'Finance', 'Marketing', 'Human resources'];

    protected static function booted(): void
    {
        static::forceDeleted(MediaUsage::forget(...));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'status' => JobStatus::class,
            'deadline' => 'date',
            'closed_at' => 'datetime',
            'published_at' => 'datetime',
            'salary_min_bdt' => 'integer',
            'salary_max_bdt' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class)->withTrashed();
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Jobs that take applications: Open, and not past the deadline (the deadline day itself counts).
     *
     * @param  Builder<JobPost>  $query
     */
    public function scopeAcceptingApplications(Builder $query): void
    {
        $query->where('status', JobStatus::Open->value)->where('deadline', '>=', JobDates::today()->toDateString());
    }

    /** True while the job takes applications (Open and not past the deadline). */
    public function isAcceptingApplications(): bool
    {
        return $this->status === JobStatus::Open && ! $this->deadline->isBefore(JobDates::today());
    }

    /** The job is closed by hand or because the deadline has passed. */
    public function isClosed(): bool
    {
        return $this->status === JobStatus::Closed
            || ($this->status === JobStatus::Open && $this->deadline->isBefore(JobDates::today()));
    }

    /** The day applications stopped: when it was closed, or the day after the deadline for a job the scheduler has not reached. */
    public function closedOn(): CarbonImmutable
    {
        return $this->closed_at ?? JobDates::closesAfter($this->deadline);
    }

    /** FR-CAR-003: closed for more than 30 days → 410 Gone. */
    public function isGone(): bool
    {
        return $this->isClosed() && $this->closedOn()->addDays(self::GONE_AFTER_DAYS)->isPast();
    }

    public function path(): string
    {
        return '/careers/'.$this->slug;
    }

    public function mediaUsageLabel(string $field): string
    {
        return "{$this->title} › Social share image";
    }

    public function mediaUsageUrl(string $field): ?string
    {
        return route('admin.careers.edit', $this);
    }

    public function replaceMediaAsset(string $field, int $fromAssetId, int $toAssetId): void {}

    public static function binType(): string
    {
        return 'job_post';
    }

    public static function binTypeLabel(): string
    {
        return 'Job post';
    }

    public static function binPermission(): string
    {
        return 'jobs.manage';
    }

    public function binLabel(): string
    {
        return $this->title;
    }

    public function binUrl(): ?string
    {
        return route('admin.careers.edit', $this);
    }

    /** DEL-05: a restored job post is never public by itself. */
    public function binTrashed(): void
    {
        if ($this->status !== JobStatus::Draft) {
            $this->forceFill(['status' => JobStatus::Draft])->saveQuietly();
        }
    }
}
