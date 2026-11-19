<?php

namespace App\Actions\Careers;

use App\Actions\Activity\LogActivity;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\User;
use App\Support\Html\BasicHtml;
use Illuminate\Support\Facades\DB;

/**
 * FR-CAR-001: creates or updates a job post with its SEO panel and media usage in one transaction.
 * The four rich-text sections are cleaned here (NFR-SEC-03). The status is part of the form: becoming
 * Open for the first time stamps `published_at`, becoming Closed stamps `closed_at`, and leaving
 * Closed clears it (FR-CAR-003). The payload is the editor form (`JobPostEditor::form`, validated by
 * `SaveJobPostRequest`).
 */
final class SaveJobPost
{
    public function __construct(
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly SyncJobPostMediaUsages $syncMediaUsages,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of the job post Form Request
     * @param  bool  $log  false for a duplicate and for seeding (the caller logs)
     */
    public function handle(?User $actor, array $data, ?JobPost $job = null, bool $log = true): JobPost
    {
        return DB::transaction(function () use ($actor, $data, $job, $log): JobPost {
            $creating = $job === null;
            $job ??= new JobPost;
            $status = JobStatus::from((string) $data['status']);
            $wasStatus = $job->status ?? null;

            $job->fill([
                'title' => trim((string) $data['title']),
                'slug' => $data['slug'],
                'department' => trim((string) $data['department']),
                'office_id' => ($data['office_id'] ?? null) ?: null,
                'employment_type' => $data['employment_type'],
                'experience' => $this->text($data['experience'] ?? null),
                'salary_text' => $this->text($data['salary_text'] ?? null),
                'salary_min_bdt' => filled($data['salary_min_bdt'] ?? null) ? (int) $data['salary_min_bdt'] : null,
                'salary_max_bdt' => filled($data['salary_max_bdt'] ?? null) ? (int) $data['salary_max_bdt'] : null,
                'summary' => trim((string) $data['summary']),
                'description' => BasicHtml::clean($data['description'] ?? null),
                'responsibilities' => BasicHtml::clean($data['responsibilities'] ?? null),
                'requirements' => BasicHtml::clean($data['requirements'] ?? null),
                'benefits' => BasicHtml::clean($data['benefits'] ?? null),
                'deadline' => $data['deadline'],
                'status' => $status,
            ]);

            if ($status === JobStatus::Open && $job->published_at === null) {
                $job->published_at = now();
            }

            if ($status === JobStatus::Closed && $wasStatus !== JobStatus::Closed) {
                $job->closed_at = now();
            } elseif ($status !== JobStatus::Closed) {
                $job->closed_at = null;
            }

            $changed = array_keys($job->getDirty());
            $job->save();

            $this->saveSeoMeta->handle($job, $data['seo'] ?? null);
            $this->syncMediaUsages->handle($job);

            if ($log) {
                $this->logActivity->handle(
                    'jobs',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Job post created: ' : 'Job post updated: ').$job->title,
                    $actor,
                    $job,
                    ['label' => $job->title, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at']))],
                );
            }

            return $job;
        });
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
