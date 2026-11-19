<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Support\RecycleBin\BinBehaviour;
use App\Support\RecycleBin\Trashable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * DM-APP: one application to a job post, or a general CV (`job_post_id` null, FR-CAR-004). Created only
 * by `SubmitJobApplication`. The CV is on the private disk and leaves it only through an authorised
 * download (FR-LEAD-011). Delete = Recycle bin for 365 days; an application nobody has touched for
 * 12 months is removed with its CV (NFR-PRIV-04).
 *
 * @property int $id
 * @property int|null $job_post_id
 * @property string $reference
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string|null $current_position
 * @property int|null $years_experience
 * @property string|null $expected_salary
 * @property string|null $linkedin_url
 * @property string $cv_path
 * @property string $cv_name
 * @property int $cv_size
 * @property string|null $cover_letter
 * @property ApplicationStatus $status
 * @property string|null $review_reason
 * @property string|null $notes
 * @property string $consent_text
 * @property string|null $ip_hash
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Guarded([])]
class JobApplication extends Model implements Trashable
{
    use BinBehaviour, SoftDeletes;

    /** The private disk (`storage/app/private`), like the landowner documents. */
    public const string DISK = 'local';

    /** NFR-PRIV-04: a CV is deleted this many days after the application's last update. */
    public const int RETENTION_DAYS = 365;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'years_experience' => 'integer',
            'cv_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<JobPost, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPost::class, 'job_post_id')->withTrashed();
    }

    /** The folder of this application's CV on the private disk. */
    public function cvDirectory(): string
    {
        return "careers/{$this->reference}";
    }

    public static function binType(): string
    {
        return 'job_application';
    }

    public static function binTypeLabel(): string
    {
        return 'Application';
    }

    public static function binPermission(): string
    {
        return 'applications.manage';
    }

    /** DEL-06: applications stay 365 days. */
    public static function binRetentionDays(): ?int
    {
        return 365;
    }

    public function binLabel(): string
    {
        return $this->name;
    }

    public function binUrl(): ?string
    {
        return route('admin.applications.index', ['application' => $this->id]);
    }

    /** DEL-07: the CV goes with the row, never before. */
    public function binForceDeleting(): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->cvDirectory());
    }
}
